<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';

$user = require_login();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

try {
    match ($action) {
        'state' => state(),
        'create_game' => create_game($user),
        'mark_number' => mark_number($user),
        'delete_number' => delete_number($user),
        'pause_game' => change_game_status($user, 'pausada'),
        'resume_game' => change_game_status($user, 'en_curso'),
        'finish_game' => change_game_status($user, 'finalizada'),
        'reset_game' => reset_game($user),
        default => json_response(['ok' => false, 'message' => 'Accion no reconocida.'], 404),
    };
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => 'Error del sistema: ' . $e->getMessage()], 500);
}

function ensure_operator(array $user): void
{
    if ($user['rol'] !== 'operador') {
        json_response(['ok' => false, 'message' => 'No tiene permiso para realizar esta accion.'], 403);
    }
}

function state(): void
{
    $gameId = isset($_GET['partida_id']) ? (int) $_GET['partida_id'] : 0;
    $game = $gameId > 0 ? get_game($gameId) : active_or_latest_game();

    if (!$game) {
        json_response([
            'ok' => true,
            'game' => null,
            'numbers' => [],
            'marked' => [],
            'last' => null,
            'count' => 0,
        ]);
    }

    $numbers = get_marked_numbers((int) $game['id']);
    $marked = [];
    foreach ($numbers as $row) {
        $marked[(int) $row['numero']] = true;
    }

    json_response([
        'ok' => true,
        'game' => normalize_game($game),
        'numbers' => array_map('normalize_number', $numbers),
        'marked' => array_keys($marked),
        'last' => $numbers ? normalize_number($numbers[count($numbers) - 1]) : null,
        'count' => count($numbers),
    ]);
}

function create_game(array $user): void
{
    ensure_operator($user);

    $name = trim($_POST['nombre_partida'] ?? '');
    if ($name === '') {
        $name = 'Partida ' . date('Y-m-d H:i');
    }

    $stmt = db()->prepare(
        "INSERT INTO partidas_bingo (nombre_partida, fecha, estado, usuario_creador, fecha_inicio)
         VALUES (?, CURDATE(), 'en_curso', ?, NOW())"
    );
    $stmt->execute([$name, $user['id']]);
    $gameId = (int) db()->lastInsertId();

    log_action((int) $user['id'], $gameId, 'crear_partida', 'Creo la partida ' . $name);
    json_response(['ok' => true, 'message' => 'Partida creada.', 'game_id' => $gameId]);
}

function mark_number(array $user): void
{
    ensure_operator($user);

    $gameId = (int) ($_POST['partida_id'] ?? 0);
    $number = (int) ($_POST['numero'] ?? 0);

    if ($number < 1 || $number > 75) {
        json_response(['ok' => false, 'message' => 'El numero debe estar entre 1 y 75.'], 422);
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $game = lock_game($gameId);
        if (!$game) {
            throw new RuntimeException('La partida no existe.');
        }
        if ($game['estado'] !== 'en_curso') {
            throw new RuntimeException('Solo se pueden marcar numeros en una partida en curso.');
        }

        $exists = $pdo->prepare('SELECT id FROM numeros_marcados WHERE partida_id = ? AND numero = ?');
        $exists->execute([$gameId, $number]);
        if ($exists->fetch()) {
            $pdo->rollBack();
            json_response(['ok' => false, 'message' => 'El numero ' . bingo_code($number) . ' ya fue marcado.'], 409);
        }

        $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(orden_salida), 0) + 1 AS next_order FROM numeros_marcados WHERE partida_id = ?');
        $orderStmt->execute([$gameId]);
        $order = (int) $orderStmt->fetchColumn();
        $letter = bingo_letter($number);
        $code = bingo_code($number);

        $insert = $pdo->prepare(
            'INSERT INTO numeros_marcados (partida_id, numero, letra, codigo_bingo, orden_salida, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([$gameId, $number, $letter, $code, $order, $user['id']]);

        log_action((int) $user['id'], $gameId, 'marcar_numero', 'Marco el numero ' . $code);
        $pdo->commit();
        json_response(['ok' => true, 'message' => $code . ' marcado.', 'code' => $code]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['ok' => false, 'message' => $e->getMessage()], 422);
    }
}

function delete_number(array $user): void
{
    ensure_operator($user);

    $gameId = (int) ($_POST['partida_id'] ?? 0);
    $number = (int) ($_POST['numero'] ?? 0);
    $game = get_game($gameId);

    if (!$game || $game['estado'] === 'finalizada') {
        json_response(['ok' => false, 'message' => 'No se puede corregir una partida finalizada o inexistente.'], 422);
    }

    $stmt = db()->prepare('SELECT * FROM numeros_marcados WHERE partida_id = ? AND numero = ?');
    $stmt->execute([$gameId, $number]);
    $marked = $stmt->fetch();

    if (!$marked) {
        json_response(['ok' => false, 'message' => 'Ese numero no esta marcado.'], 404);
    }

    db()->prepare('DELETE FROM numeros_marcados WHERE id = ?')->execute([$marked['id']]);
    db()->prepare(
        'UPDATE numeros_marcados
         SET orden_salida = orden_salida - 1
         WHERE partida_id = ? AND orden_salida > ?'
    )->execute([$gameId, $marked['orden_salida']]);

    log_action((int) $user['id'], $gameId, 'corregir_numero', 'Elimino el numero ' . $marked['codigo_bingo']);
    json_response(['ok' => true, 'message' => $marked['codigo_bingo'] . ' eliminado.']);
}

function change_game_status(array $user, string $status): void
{
    ensure_operator($user);

    $gameId = (int) ($_POST['partida_id'] ?? 0);
    $game = get_game($gameId);
    if (!$game) {
        json_response(['ok' => false, 'message' => 'La partida no existe.'], 404);
    }

    $finishSql = $status === 'finalizada' ? ', fecha_fin = NOW()' : ', fecha_fin = NULL';
    db()->prepare("UPDATE partidas_bingo SET estado = ? $finishSql WHERE id = ?")->execute([$status, $gameId]);

    $labels = [
        'pausada' => ['pausar_partida', 'Pauso la partida'],
        'en_curso' => ['reanudar_partida', 'Reanudo la partida'],
        'finalizada' => ['finalizar_partida', 'Finalizo la partida'],
    ];
    log_action((int) $user['id'], $gameId, $labels[$status][0], $labels[$status][1]);

    json_response(['ok' => true, 'message' => 'Estado actualizado a ' . status_label($status) . '.']);
}

function reset_game(array $user): void
{
    ensure_operator($user);

    $gameId = (int) ($_POST['partida_id'] ?? 0);
    $game = get_game($gameId);
    if (!$game) {
        json_response(['ok' => false, 'message' => 'La partida no existe.'], 404);
    }

    db()->prepare('DELETE FROM numeros_marcados WHERE partida_id = ?')->execute([$gameId]);
    db()->prepare("UPDATE partidas_bingo SET estado = 'en_curso', fecha_fin = NULL WHERE id = ?")->execute([$gameId]);
    log_action((int) $user['id'], $gameId, 'reiniciar_partida', 'Reinicio la partida y borro sus numeros marcados');

    json_response(['ok' => true, 'message' => 'Partida reiniciada.']);
}

function lock_game(int $gameId): ?array
{
    $stmt = db()->prepare('SELECT * FROM partidas_bingo WHERE id = ? FOR UPDATE');
    $stmt->execute([$gameId]);

    return $stmt->fetch() ?: null;
}

function normalize_game(array $game): array
{
    return [
        'id' => (int) $game['id'],
        'nombre_partida' => $game['nombre_partida'],
        'fecha' => $game['fecha'],
        'estado' => $game['estado'],
        'estado_label' => status_label($game['estado']),
        'creador' => $game['creador'] ?? '',
        'fecha_inicio' => $game['fecha_inicio'],
        'fecha_fin' => $game['fecha_fin'],
    ];
}

function normalize_number(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'numero' => (int) $row['numero'],
        'letra' => $row['letra'],
        'codigo_bingo' => $row['codigo_bingo'],
        'orden_salida' => (int) $row['orden_salida'],
        'usuario_nombre' => $row['usuario_nombre'] ?? '',
        'fecha_hora' => $row['fecha_hora'],
    ];
}
