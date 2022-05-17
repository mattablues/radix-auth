<?php

declare(strict_types=1);

/**
 * Project name: radix-model
 * Filename: Database.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-13, 12:58
 */

namespace Radix\Database;

use JetBrains\PhpStorm\Pure;
use PDO;
use PDOStatement;

/**
 * Class Database
 * @package Radix\Database
 */
class Database
{
    private PDO $connection;

    #[Pure] public function __construct(DatabaseConnection $connection)
    {
        $this->connection = $connection->get();
    }

    /**
     * Run database query
     * @param  string  $sql
     * @param  array  $params
     * @return PDOStatement|null
     */
    public function query(string $sql, array $params = []): ?PDOStatement
    {
        if (!$params) {
            return $this->connection->query($sql);
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public function getConnection(): ?PDO
    {
        return $this->connection;
    }
}
