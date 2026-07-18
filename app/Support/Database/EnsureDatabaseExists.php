<?php

namespace App\Support\Database;

use PDO;
use PDOException;

class EnsureDatabaseExists
{
    public function forDefaultConnection(): void
    {
        $name = config('database.default');

        /** @var array<string, mixed> $config */
        $config = config("database.connections.{$name}");

        if (! in_array($config['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            return;
        }

        $this->createIfMissing($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createIfMissing(array $config): void
    {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s', (string) $config['host'], (string) $config['port']),
                (string) $config['username'],
                (string) $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
        } catch (PDOException) {
            return;
        }

        $database = str_replace('`', '``', (string) $config['database']);
        $charset = (string) ($config['charset'] ?? 'utf8mb4');
        $collation = (string) ($config['collation'] ?? 'utf8mb4_unicode_ci');

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset} COLLATE {$collation}");
    }
}
