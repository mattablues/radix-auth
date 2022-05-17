<?php

declare(strict_types=1);

/**
 * Project name: radix-session
 * Filename: FlashException.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-15, 17:31
 */

namespace Radix\Session\Exception;

/**
 * Class FlashException
 * @package Radix\Session\Exception
 */
class FlashException extends \Exception
{
    public static function messageTypeNotAllowed(string $type): static
    {
        return new static("Flash message type $type is not allowed.");
    }
}
