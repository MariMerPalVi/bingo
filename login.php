<?php
require_once __DIR__ . '/config/helpers.php';

if (current_user()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['usuario'] ?? '');
    $password = $_POST['contrasena'] ?? '';

    $stmt = db()->prepare(
        "SELECT u.*, r.nombre AS rol
         FROM usuarios u
         JOIN roles r ON r.id = u.rol_id
         WHERE u.usuario = ? AND u.estado = 'activo'"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['contrasena'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'nombre' => $user['nombre'],
            'usuario' => $user['usuario'],
            'rol' => $user['rol'],
        ];
        csrf_token();
        log_action((int) $user['id'], null, 'login', 'Inicio de sesion');
        header('Location: index.php');
        exit;
    }

    $error = 'Usuario o contrasena incorrectos.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso | Sistema Bingo</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=20260528-2">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-panel">
            <div class="login-brand">
                <img src="assets/img/las-naves-logo.png" alt="Las Naves Cooperativa de Ahorro y Credito">
            </div>
            <h1>Bingo Las Naves</h1>
            <p>Gestion de partidas para evento cooperativo</p>

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="form-stack">
                <label>
                    Usuario
                    <input name="usuario" autocomplete="username" required autofocus>
                </label>
                <label>
                    Contrasena
                    <input type="password" name="contrasena" autocomplete="current-password" required>
                </label>
                <button class="primary-button" type="submit">Ingresar</button>
            </form>
        </section>
    </main>
</body>
</html>
