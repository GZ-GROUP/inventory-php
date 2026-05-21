<?php
declare(strict_types=1);

/**
 * Carga variables desde un archivo .env al entorno PHP.
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Quitar comillas opcionales del valor
        $value = trim($value, '"\'');
        if ($key !== '') {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

/**
 * Devuelve una instancia PDO conectada a PostgreSQL.
 * Lee las credenciales desde el .env ubicado un nivel arriba de /src.
 */
function getConnection(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    loadEnv(__DIR__ . '/../.env');

    $host = $_ENV['DB_HOST']     ?? 'localhost';
    $port = $_ENV['DB_PORT']     ?? '5432';
    $name = $_ENV['DB_NAME']     ?? 'inventario_utp';
    $user = $_ENV['DB_USER']     ?? 'postgres';
    $pass = $_ENV['DB_PASSWORD'] ?? '';

    $dsn = "pgsql:host={$host};port={$port};dbname={$name};options='--client_encoding=UTF8'";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}