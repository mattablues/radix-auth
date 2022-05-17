<?php

declare(strict_types=1);

/**
 * Project name: radix-validator
 * Filename: Clean.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-12, 21:06
 */

namespace Radix\Utilities;

/**
 * Class Clean
 * @package Radix\Utilities
 */
class Clean
{
    /**
     * Remove whitespace
     * @param  string  $string
     * @return string
     */
    public static function whitespace(string $string): string
    {
        return preg_replace('/\s+/', '', $string);
    }

    /**
     * Clean text to have only one space between words
     * @param  string  $text
     * @return string
     */
    public static function text(string $text): string
    {
        return trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $text)));
    }
}
