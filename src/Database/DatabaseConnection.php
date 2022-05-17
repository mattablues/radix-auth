<?php

declare(strict_types=1);

/**
 * Project name: radix-model
 * Filename: DatabaseConnection.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-13, 12:50
 */

namespace Radix\Database;

use PDO;
use PDOException;
use Radix\Configuration\Config;
use Radix\Database\Exception\DatabaseException;

/**
 * Class DatabaseConnection
 * @package Radix\Database
 */
class DatabaseConnection
{
    protected ?PDO $db = null;

    /**
     * DatabaseConnection Constructor
     * @throws DatabaseException
     */
    public function __construct()
    {

        if ($this->db === null) {
            try {
                $config = new Config();

                $dsn = $config->env('DB_DRIVER') .
                    ':host='   . $config->env('DB_HOST') .
                    ';port='   . $config->env('DB_PORT') .
                    ';dbname=' . $config->env('DB_NAME') .
                    ';charset' . $config->env('DB_CHAR');

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_FOUND_ROWS => true,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING,
                ];

                $this->db = new PDO($dsn, $config->env('DB_USER'), $config->env('DB_PASS'), $options);

            } catch (PDOException $e) {
                throw DatabaseException::connectionFailed($e->getMessage());
            }
        }
    }

    /**
     * Get database connection
     * @return PDO
     */
    public function get(): PDO
    {
        return $this->db;
    }
}
