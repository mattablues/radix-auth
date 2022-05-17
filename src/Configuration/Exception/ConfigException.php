<?php

declare(strict_types=1);

/**
 * Project name: radix-config
 * Filename: ConfigException.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-10, 14:17
 */

namespace Radix\Configuration\Exception;

class ConfigException extends \Exception
{
    public static function fileNotFound(string|array $file): static
    {
        if (is_array($file)) {
            $file = implode('.php, ', $file) . '.php';
        }

        return new static("One or more config files not found: $file");
    }

    public static function arrayKeyAlreadyExist($key): static
    {
        return new static("Array key: $key already exist.");
    }
}
