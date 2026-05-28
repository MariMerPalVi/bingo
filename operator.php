<?php
require_once __DIR__ . '/config/helpers.php';
$user = require_role('operador');

$games = db()->query(
    "SELECT p.*, COUNT(n.id) AS total
     FROM partidas_bingo p
     LEFT JOIN numeros_marcados n ON n.partida_id = p.id
     GROUP BY p.id
     ORDER BY p.id DESC
     LIMIT 20"
)->fetchAll();
$currentGame = active_or_latest_game();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operador | Sistema Bingo</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body data-page="operator" data-csrf="<?= htmlspecialchars(csrf_token()) ?>">
    <header class="topbar">
        <div class="topbar-brand">
            <img src="assets/img/las-naves-logo.png" alt="Las Naves">
            <div>
                <strong>Bingo Las Naves</strong>
                <span>Operador: <?= htmlspecialchars($user['nombre']) ?></span>
            </div>
        </div>
        <nav>
            <a href="viewer.php" target="_blank">Abrir visualizador</a>
            <a href="history.php">Historial</a>
            <a href="logout.php">Salir</a>
        </nav>
    </header>

    <main class="app-layout">
        <aside class="side-panel">
            <h2>Partidas</h2>
            <form id="create-game-form" class="compact-form">
                <input type="hidden" name="action" value="create_game">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input name="nombre_partida" placeholder="Nombre de partida">
                <button class="primary-button" type="submit">Nueva partida</button>
            </form>

            <div class="game-list">
                <?php foreach ($games as $game): ?>
                    <button class="game-option <?= $currentGame && (int) $currentGame['id'] === (int) $game['id'] ? 'active' : '' ?>"
                            type="button"
                            data-game-id="<?= (int) $game['id'] ?>">
                        <span><?= htmlspecialchars($game['nombre_partida']) ?></span>
                        <small><?= status_label($game['estado']) ?> · <?= (int) $game['total'] ?>/75</small>
                    </button>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="operator-workspace" data-game-id="<?= $currentGame ? (int) $currentGame['id'] : 0 ?>">
            <div class="status-row">
                <div>
                    <p class="eyebrow">Partida actual</p>
                    <h1 id="game-title"><?= $currentGame ? htmlspecialchars($currentGame['nombre_partida']) : 'Sin partida' ?></h1>
                </div>
                <div class="status-actions">
                    <span id="game-status" class="status-pill">--</span>
                    <button data-action="pause_game" class="ghost-button">Pausar</button>
                    <button data-action="resume_game" class="ghost-button">Reanudar</button>
                    <button data-action="finish_game" data-confirm="Finalizar esta partida?" class="danger-button">Finalizar</button>
                    <button data-action="reset_game" data-confirm="Reiniciar la partida y borrar sus numeros?" class="danger-button">Reiniciar</button>
                </div>
            </div>

            <div id="message" class="message" hidden></div>

            <div class="operator-grid manual-only">
                <section class="last-card">
                    <p>Ultimo numero</p>
                    <strong id="last-number">--</strong>
                    <span id="counter">0 de 75 numeros</span>
                </section>

                <section class="entry-panel">
                    <h2>Registrar numero</h2>
                    <form id="mark-form" class="mark-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input id="manual-number" name="numero" type="number" min="1" max="75" placeholder="1-75">
                        <button class="primary-button" type="submit">Marcar salido</button>
                    </form>
                    <div id="number-buttons" class="number-pad"></div>
                </section>
            </div>

            <section class="history-section">
                <h2>Historial de salida</h2>
                <div id="history-list" class="history-list"></div>
            </section>
        </section>
    </main>

    <script src="assets/js/app.js"></script>
</body>
</html>
