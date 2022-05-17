<?php

declare(strict_types=1);

/**
 * Project name: radix-model
 * Filename: DatabaseException.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-13, 12:52
 */

namespace Radix\Database\Exception;

/**
 * Class DatabaseException
 * @package Radix\Database\Exception
 */
class DatabaseException extends \PDOException
{
    public static function connectionFailed(string $message): static
    {
        return new static("Database connection failed: $message", 500);
    }
}
