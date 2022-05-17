<?php

declare(strict_types=1);

/**
 * Project name: radix-session
 * Filename: Session.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-15, 15:26
 */

namespace Radix\Session;

use JetBrains\PhpStorm\Pure;

/**
 * Class Session
 * @package Radix\Session
 */
class Session
{
    private bool $isStarted = false;

    /**
     * Check if session is started
     * @return bool
     */
    public function isStarted(): bool
    {
        $this->isStarted = session_status() === PHP_SESSION_ACTIVE;

        return $this->isStarted;
    }

    /**
     * Start session
     * @return bool
     */
    public function start(): bool
    {
        if ($this->isStarted) {
            return true;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->isStarted = true;
            return true;
        }

        session_start();
        $this->isStarted = true;

        return true;
    }

    /**
     * Check if session has key
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Get session variable by key
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    #[Pure] public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $_SESSION[$key];
        }

        return $default;
    }

    /**
     * Set session variable
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Clear all session variables
     * @return void
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * Remove session variable by key
     * @param  string  $key
     * @return void
     */
    public function remove(string $key): void
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }
}
