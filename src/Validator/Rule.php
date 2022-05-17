<?php

declare(strict_types=1);

/**
 * Project name: radix-validator
 * Filename: Rule.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-12, 20:57
 */

namespace Radix\Validator;

use Radix\Database\DatabaseConnection;
use Radix\Utilities\Check;
use Radix\Utilities\Clean;
use Radix\Utilities\Prep;

/**
 * Class Rule
 * @package Radix\Validator
 */
class Rule
{
    private array $rule;
    private mixed $data;

    /**
     * Set rules and data
     * @param  array  $rules
     * @param  mixed  $data
     */
    public function set(array $rules, mixed $data): void
    {
        $this->rule = $rules;
        $this->data = $data;
    }

    /**
     * Required
     * @return bool
     */
    public function required(): bool
    {
        if ($this->rule['required'] === false && strlen($this->data) === 0) {
            return false;
        }

        return $this->rule['required'] === true && Clean::whitespace($this->data) === '';
    }

    /**
     * Must not contain spaces
     * @return bool
     */
    public function space(): bool
    {
        if ($this->rule['required'] === false && strlen($this->data) === 0) {
            return false;
        }

        return $this->rule['space'] && Check::whitespace($this->data);
    }

    /**
     * Min length
     * @return bool
     */
    public function min(): bool
    {
        if ($this->rule['required'] === false && strlen($this->data) === 0) {
            return false;
        }

        return $this->rule['min'] > strlen($this->data);
    }

    /**
     * Max length
     * @return bool
     */
    public function max(): bool
    {
        if ($this->rule['required'] === false && strlen($this->data) === 0) {
            return false;
        }

        return $this->rule['max'] < strlen($this->data);
    }

    /**
     * Must contain numbers
     * @return bool
     */
    public function num(): bool
    {
        if ($this->rule['required'] === false && strlen($this->data) === 0) {
            return false;
        }

        preg_match_all('/\d+/', $this->data, $matches);

        return Prep::matches($matches[0]) < $this->rule['num'];
    }

    /**
     * Must contain letters
     * @return bool
     */
    public function let(): bool
    {
        if ($this->rule['required'] === false && strlen($this->data) === 0) {
            return false;
        }

        preg_match_all('/[a-åäö]+/i', $this->data, $matches);

        return Prep::matches($matches[0]) < $this->rule['let'];
    }

    /**
     * Must contain special characters
     * @return bool
     */
    public function spec(): bool
    {
        if ($this->rule['required'] === false && strlen($this->data) === 0) {
            return false;
        }

        preg_match_all('/[\'\/~`!@#\$%^&*()_\-+={}\[\]|;:"<>,.?\\\]/', $this->data, $matches);

        return Prep::matches($matches[0]) < $this->rule['spec'];
    }

    /**
     * Must contain only digits
     * @return bool
     */
    public function numeric(): bool
    {
        if ($this->rule['required'] === false && strlen($this->data) === 0) {
            return false;
        }

        return !is_numeric($this->data);
    }

    /**
     * Must contain only letters
     * @return bool
     */
    public function letters(): bool
    {
        if ($this->rule['letters'] === false && strlen($this->data) === 0) {
            return false;
        }

        return !ctype_alpha($this->data);
    }

    /**
     * Valid email
     * @return bool
     */
    public function email(): bool
    {
        return !filter_var($this->data, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Valid URL
     * @return bool
     */
    public function url(): bool
    {
        return !filter_var($this->data, FILTER_VALIDATE_URL);
    }

    /**
     * Match field
     * @param  mixed  $match
     * @return bool
     */
    public function match(mixed $match): bool
    {
        return $match !== $this->data;
    }

    /**
     * Is unique
     * @param  string|null  $table
     * @param  string  $field
     * @return bool
     */
    public function unique(?string $table, string $field): bool
    {
        if (isset($table)) {
/*            $auth = new Auth();
            $user = $auth->user();

            if ($this->rule['unique'] === 'except' && $auth->isLoggedIn() && $this->data === $user->$field) {
                return false;
            }*/

            $connection = new DatabaseConnection();
            $db = $connection->getConnection();

            $stmt = $db->prepare("SELECT $field FROM $table WHERE $field = :$field");
            $stmt->execute([$field => $this->data]);

            return $stmt->rowCount() > 0;
        }

        return false;
    }
}
