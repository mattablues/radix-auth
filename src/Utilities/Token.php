<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: Token.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-18, 10:59
 */

namespace Radix\Utilities;

use Exception;
use Radix\Configuration\Config;

class Token
{
    private string $token;

    /**
     * Token constructor
     * @param  string|null  $tokenValue
     * @throws Exception
     */
    public function __construct(?string $tokenValue = null)
    {
        if ($tokenValue) {
            $this->token = $tokenValue;
        } else {
            $this->token = bin2hex(random_bytes(16)); // 16 bytes = 128 bits = 32 hex characters
        }
    }

    /**
     * Returns token
     * @return string
     */
    public function value(): string
    {
        return $this->token;
    }

    /**
     * Returns token value
     * @return string
     */
    public function hash(): string
    {
        $config = new Config();
        return hash_hmac('sha256', $this->token, $config->env('TOKEN_HMAC'));
    }
}
