<?php
require_once __DIR__ . '/config/helpers.php';
$user = require_login();

$games = db()->query(
    "SELECT p.*, u.nombre AS creador, COUNT(n.id) AS total
     FROM partidas_bingo p
     JOIN usuarios u ON u.id = p.usuario_creador
     LEFT JOIN numeros_marcados n ON n.partida_id = p.id
     GROUP BY p.id
     ORDER BY p.id DESC"
)->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historial | Sistema Bingo</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <div>
            <strong>Historial de partidas</strong>
            <span><?= htmlspecialchars($user['nombre']) ?></span>
        </div>
        <nav>
            <a href="index.php">Panel</a>
            <a href="logout.php">Salir</a>
        </nav>
    </header>

    <main class="history-page">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Partida</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Creador</th>
                    <th>Numeros</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td><?= htmlspecialchars($game['nombre_partida']) ?></td>
                        <td><?= htmlspecialchars($game['fecha']) ?></td>
                        <td><?= status_label($game['estado']) ?></td>
                        <td><?= htmlspecialchars($game['creador']) ?></td>
                        <td><?= (int) $game['total'] ?>/75</td>
                        <td><?= htmlspecialchars($game['fecha_inicio']) ?></td>
                        <td><?= htmlspecialchars($game['fecha_fin'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
