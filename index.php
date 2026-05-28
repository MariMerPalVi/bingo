<?php
require_once __DIR__ . '/config/helpers.php';

$user = current_user();
if (!$user) {
    header('Location: login.php');
    exit;
}

header('Location: ' . ($user['rol'] === 'operador' ? 'operator.php' : 'viewer.php'));
exit;
