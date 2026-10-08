<?php

function db(): PDO
{
    $envFile = dirname(__DIR__) . '/.env';

    $env = [];

    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(
                explode('=', $line, 2),
                2,
                ''
            );

            $env[trim($key)] = trim($value);
        }
    }

    $host = $env['DB_HOST'] ?? 'localhost';
    $port = $env['DB_PORT'] ?? '5432';
    $name = $env['DB_NAME'] ?? 'aml_engine';
    $user = $env['DB_USER'] ?? 'postgres';
    $password = $env['DB_PASSWORD'] ?? '';

    $dsn = "pgsql:host=$host;port=$port;dbname=$name";

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}