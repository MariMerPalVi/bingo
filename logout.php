<?php
require_once __DIR__ . '/config/helpers.php';

$user = current_user();
if ($user) {
    log_action((int) $user['id'], null, 'logout', 'Cierre de sesion');
}

$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
