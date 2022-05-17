<?php

declare(strict_types=1);

/**
 * Project name: radix-model
 * Filename: Model.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-13, 14:06
 */

namespace Radix\Model;

use PDO;
use PDOStatement;
use Radix\Database\Database;
use Radix\Database\DatabaseConnection;

/**
 * Class Model
 * @package Radix\Model
 */
abstract class Model
{
    protected string $table = '';
    private ?PDO $db = null;

    /**
     * Find row
     * @param  string  $col
     * @param  string  $operator
     * @param  mixed  $value
     * @return Model|null
     */
    public function find(string $col, string $operator, mixed $value): ?static
    {
        $params = [$col => $value];
        $sql = "SELECT * FROM $this->table WHERE $col $operator :$col";

        return $this->dbQuery($sql, $params)->fetchAll(PDO::FETCH_CLASS, static::class)[0] ?? null;
    }

    /**
     * Get rows
     * @param  string|null  $col
     * @param  string|null  $operator
     * @param  mixed|null  $value
     * @return array|null
     */
    public function get(?string $col = null, ?string $operator = null, mixed $value = null): ?array
    {
        $params = [];

        $sql = "SELECT * FROM $this->table";

        if ($col && $operator && $value) {
            $params = [$col => $value];
            $sql .= " WHERE $col $operator :$col";
        }

        return $this->dbQuery($sql, $params)->fetchAll(PDO::FETCH_CLASS, static::class) ?? null;
    }

    /**
     * Create row
     * @param  array  $data
     * @return int|null
     */
    public function create(array $data): ?int
    {
        $fields = array_keys($data);

        $sql  = "INSERT INTO $this->table (" . implode(', ', $fields) . ") ";
        $sql .= "VALUES (:" . implode(', :', $fields) . ")";

        $this->dbQuery($sql, $data);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update row
     * @param  array  $data
     * @param  string  $field
     * @param  mixed  $value
     * @return int|null
     */
    public function update(array $data, string $field, mixed $value): ?int
    {
        $fields = array_keys($data);

        $sql = "UPDATE $this->table SET ";

        foreach ($fields as $col) {
            $sql .= "$col = :$col, ";
        }

        $prefix = '';

        if (array_key_exists($field, $data)) {
            $prefix = '1';
        }

        $sql = substr($sql,0, strrpos($sql, ', '));
        $sql .= " WHERE $field = :{$field}{$prefix}";

        $data[$field . $prefix] = $value;
        $stmt = $this->dbQuery($sql, $data);

        return $stmt->rowCount() ?? null;
    }

    /**
     * Delete row
     * @param  string  $col
     * @param  string  $operator
     * @param  mixed  $value
     * @return int|null
     */
    public function delete(string $col, string $operator, mixed $value): ?int
    {
        $params = [$col => $value];

        $sql = "DELETE FROM $this->table WHERE $col $operator :$col LIMIT 1";

        $stmt = $this->dbQuery($sql, $params);

        return $stmt->rowCount() ?? null;
    }

    /**
     * Search rows
     * @param  string  $col
     * @param  string  $value
     * @param  string  $search
     * @return bool|array
     */
    public function search(string $col, string $value, string $search = 'default'): ?array
    {
        $params = [];

        $sql = "SELECT * FROM $this->table WHERE $col LIKE :$col";

        if ($search === 'default') {
            $params = [$col => '%' . $value . '%'];
        }

        if ($search === 'start') {
            $params = [$col => $value . '%'];
        }

        if ($search === 'end') {
            $params = [$col => '%' . $value];
        }

        return $this->dbQuery($sql, $params)->fetchAll(PDO::FETCH_CLASS, static::class) ?? null;
    }

    /**
     * @param  string  $sql
     * @param  array  $params
     * @return PDOStatement|null
     */
    protected function dbQuery(string $sql, array $params = []): ?PDOStatement
    {
        $connection = new DatabaseConnection();
        $db = new Database($connection);

        $this->db = $db->getConnection();

        return $db->query($sql, $params);
    }
}
