<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'sistema_bingo';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
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
