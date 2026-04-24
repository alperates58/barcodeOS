<?php

declare(strict_types=1);

namespace Dmc;

use PDO;

final class Database
{
    public static function connect(array $config): PDO
    {
        $db = $config['db'];
        $charset = $db['charset'] ?? 'utf8mb4';
        $port = (int)($db['port'] ?? 3306);
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $port,
            $db['database'],
            $charset
        );

        return new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public static function connectWithoutDatabase(array $db): PDO
    {
        $charset = $db['charset'] ?? 'utf8mb4';
        $port = (int)($db['port'] ?? 3306);
        $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $db['host'], $port, $charset);

        return new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}

