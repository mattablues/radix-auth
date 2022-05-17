<?php

declare(strict_types=1);

/**
 * Project name: radix-validator
 * Filename: helpers.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-12, 20:46
 */

use Radix\Configuration\Config;
use Radix\Configuration\Exception\ConfigException;

if (!function_exists('config')) {
    /**
     * Get config single value by key
     * @param  string  $key
     * @return mixed
     * @throws ConfigException
     */
    function config(string $key): mixed
    {
        if (str_contains($key, '.')) {
            $parts = explode('.', $key, 2);
            $config = Config::make($parts[0]);

            return $config->get($parts[0] . '.' . $parts[1]);
        }

        $config = Config::make($key);

        return $config->get();
    }
}

if (!function_exists('env')) {
    /**
     * Get env single value by key
     * @param  string  $key
     * @return mixed
     */
    function env(string $key): mixed
    {
        $config = new Config();

        return $config->env($key);
    }
}
