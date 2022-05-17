<?php

declare(strict_types=1);

/**
 * Project name: radix-validator
 * Filename: Prep.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-12, 21:08
 */

namespace Radix\Utilities;

/**
 * Class Prep
 * @package Radix\Utilities
 */
class Prep
{
    /**
     * Extract matches
     * @param  array  $data
     * @return int
     */
    public static function matches(array $data): int
    {
        $matches = 0;

        if ($data) {
            foreach ($data as $key => $value) {
                $matches += strlen($value);
            }

            return $matches;
        }

        return $matches;
    }
}
