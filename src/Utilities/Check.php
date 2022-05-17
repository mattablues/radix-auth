<?php

declare(strict_types=1);

/**
 * Project name: radix-validator
 * Filename: Check.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-12, 21:11
 */

namespace Radix\Utilities;

/**
 * Class Check
 * @package Radix\Utilities
 */
class Check
{
    /**
     * Check if string contains whitespace
     * @param  string  $string
     * @return false|int
     */
    public static function whitespace(string $string): bool|int
    {
        return preg_match('/\s/',$string);
    }
}
