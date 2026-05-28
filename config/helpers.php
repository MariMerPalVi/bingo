<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    return $user;
}

function require_role(string $role): array
{
    $user = require_login();
    if ($user['rol'] !== $role) {
        header('Location: index.php');
        exit;
    }

    return $user;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(['ok' => false, 'message' => 'Sesion no valida. Recargue la pagina.'], 419);
    }
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function bingo_letter(int $number): string
{
    return match (true) {
        $number <= 15 => 'B',
        $number <= 30 => 'I',
        $number <= 45 => 'N',
        $number <= 60 => 'G',
        default => 'O',
    };
}

function bingo_code(int $number): string
{
    return bingo_letter($number) . '-' . $number;
}

function status_label(string $status): string
{
    return match ($status) {
        'en_curso' => 'En curso',
        'pausada' => 'Pausada',
        'finalizada' => 'Finalizada',
        default => $status,
    };
}

function log_action(int $userId, ?int $gameId, string $action, string $description): void
{
    $stmt = db()->prepare(
        'INSERT INTO historial_acciones (usuario_id, partida_id, accion, descripcion) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $gameId, $action, $description]);
}

function active_or_latest_game(): ?array
{
    $stmt = db()->query(
        "SELECT p.*, u.nombre AS creador
         FROM partidas_bingo p
         JOIN usuarios u ON u.id = p.usuario_creador
         ORDER BY FIELD(p.estado, 'en_curso', 'pausada', 'finalizada'), p.id DESC
         LIMIT 1"
    );

    return $stmt->fetch() ?: null;
}

function get_game(int $gameId): ?array
{
    $stmt = db()->prepare(
        "SELECT p.*, u.nombre AS creador
         FROM partidas_bingo p
         JOIN usuarios u ON u.id = p.usuario_creador
         WHERE p.id = ?"
    );
    $stmt->execute([$gameId]);

    return $stmt->fetch() ?: null;
}

function get_marked_numbers(int $gameId): array
{
    $stmt = db()->prepare(
        "SELECT n.*, u.nombre AS usuario_nombre
         FROM numeros_marcados n
         JOIN usuarios u ON u.id = n.usuario_id
         WHERE n.partida_id = ?
         ORDER BY n.orden_salida ASC"
    );
    $stmt->execute([$gameId]);

    return $stmt->fetchAll();
}
