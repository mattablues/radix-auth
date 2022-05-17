<?php

declare(strict_types=1);

/**
 * Project name: radix-session
 * Filename: Cookie.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-15, 17:33
 */

namespace Radix\Session\Cookie;

use JetBrains\PhpStorm\Pure;

/**
 * Class Cookie
 * @package Radix\Session\Cookie
 */
class Cookie
{
    /**
     * Set cookie
     * @param  string  $name
     * @param  string  $value
     * @param  int  $expires
     * @param  string  $path
     * @param  string  $domain
     * @param  bool  $secure
     * @param  bool  $httponly
     * @return void
     */
    public function set(string $name, string $value = "", int $expires = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false): void {
        setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
    }

    /**
     * Get cookie
     * @param  string  $key
     * @return mixed
     */
    #[Pure] public function get(string $key): mixed
    {
        if ($this->has($key)) {
            return $_COOKIE[$key];
        }

        return null;
    }

    /**
     * Delete cookies
     * @param  string  $name
     * @return void
     */
    public function remove(string $name): void
    {
        setcookie($name, '', time() - 42000);
    }

    /**
     * Clear cookies
     * @return void
     */
    public function clear(): void
    {
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);

            foreach($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);

                setcookie($name, '', time() - 42000);
                setcookie($name, '', time() - 42000, '/');
            }
        }
    }

    /**
     * Session exists
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_COOKIE);
    }
}
