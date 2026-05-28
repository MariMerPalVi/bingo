<?php
require_once __DIR__ . '/config/helpers.php';
require_login();
$game = active_or_latest_game();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visualizador | Sistema Bingo</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=20260528-2">
</head>
<body class="viewer-page" data-page="viewer" data-game-id="<?= $game ? (int) $game['id'] : 0 ?>">
    <main class="viewer-shell">
        <section class="viewer-hero">
            <div>
                <h1 id="viewer-game-title"><?= $game ? htmlspecialchars($game['nombre_partida']) : 'Esperando partida' ?></h1>
                <span id="viewer-status" class="status-pill">--</span>
            </div>
            <div class="viewer-last">
                <img class="viewer-last-logo" src="assets/img/las-naves-logo.png" alt="Las Naves" width="150" height="54">
                <span>Ultimo numero</span>
                <strong id="last-number">--</strong>
                <small id="counter">0 de 75 numeros</small>
            </div>
        </section>

        <section class="viewer-content">
            <div id="bingo-board" class="bingo-board large"></div>
            <aside class="viewer-history">
                <h2>Orden de salida</h2>
                <div id="history-list" class="history-list compact"></div>
            </aside>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
</body>
</html>
