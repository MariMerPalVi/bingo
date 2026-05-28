<?php
declare(strict_types=1);

$privateConfig = __DIR__ . '/local.php';
$localConfig = is_file($privateConfig) ? require $privateConfig : [];
$isLocalRequest = in_array($_SERVER['HTTP_HOST'] ?? 'localhost', ['localhost', '127.0.0.1'], true);

function database_config_value(array $config, string $key, string $envName, string $default): string
{
    if (isset($config[$key]) && $config[$key] !== '') {
        return (string) $config[$key];
    }

    $envValue = getenv($envName);
    if ($envValue !== false && $envValue !== '') {
        return (string) $envValue;
    }

    return $default;
}

$databaseConfig = [
    'host' => database_config_value($localConfig, 'host', 'DB_HOST', $isLocalRequest ? '127.0.0.1' : ''),
    'port' => database_config_value($localConfig, 'port', 'DB_PORT', '3306'),
    'name' => database_config_value($localConfig, 'name', 'DB_NAME', 'sistema_bingo'),
    'user' => database_config_value($localConfig, 'user', 'DB_USER', $isLocalRequest ? 'root' : ''),
    'pass' => database_config_value($localConfig, 'pass', 'DB_PASS', ''),
];

if (!defined('DB_HOST')) {
    define('DB_HOST', (string) $databaseConfig['host']);
    define('DB_PORT', (string) $databaseConfig['port']);
    define('DB_NAME', (string) $databaseConfig['name']);
    define('DB_USER', (string) $databaseConfig['user']);
    define('DB_PASS', (string) $databaseConfig['pass']);
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (DB_HOST === '' || DB_USER === '') {
        http_response_code(500);
        exit(
            '<!doctype html><html lang="es"><meta charset="utf-8">' .
            '<title>Configurar base de datos</title>' .
            '<body style="font-family:Arial,sans-serif;background:#f4f7fb;color:#14213d;padding:32px">' .
            '<div style="max-width:720px;background:#fff;border:1px solid #d9e1ec;border-radius:8px;padding:24px">' .
            '<h1>Falta configurar MySQL de produccion</h1>' .
            '<p>Cree el archivo privado <strong>config/local.php</strong> en el hosting con los datos MySQL del panel de InfinityFree.</p>' .
            '<p>Use <strong>config/local.example.php</strong> como plantilla. No suba credenciales al repositorio.</p>' .
            '</div></body></html>'
        );
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit(
            '<!doctype html><html lang="es"><meta charset="utf-8">' .
            '<title>Error de conexion</title>' .
            '<body style="font-family:Arial,sans-serif;background:#f4f7fb;color:#14213d;padding:32px">' .
            '<div style="max-width:720px;background:#fff;border:1px solid #d9e1ec;border-radius:8px;padding:24px">' .
            '<h1>No se pudo conectar a MySQL</h1>' .
            '<p>Verifique que MySQL este iniciado en XAMPP y que la base <strong>' . DB_NAME . '</strong> exista.</p>' .
            '<p>Configuracion actual: servidor <strong>' . DB_HOST . '</strong>, puerto <strong>' . DB_PORT . '</strong>, usuario <strong>' . DB_USER . '</strong>.</p>' .
            '<p>Detalle tecnico: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>' .
            '</div></body></html>'
        );
    }

    return $pdo;
}
