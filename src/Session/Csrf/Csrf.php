<?php

declare(strict_types=1);

/**
 * Project name: radix-session
 * Filename: Csrf.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-15, 17:27
 */

namespace Radix\Session\Csrf;

use Radix\Session\Session;
use Radix\Utility\Token;

/**
 * Class Csrf
 * @package Radix\Session\Csrf
 */
class Csrf
{
    private string $token;
    private Session $session;

    /**
     * Csrf Constructor
     * @param  string  $token
     */
    public function __construct(string $token)
    {
        $this->session = new Session();
        $this->session->start();
        $this->token = $token;
    }

    /**
     * Token
     * @param  Session  $session
     * @return string
     */
    public static function token(Session $session): string
    {
        $token = new Token();
        $session->set('csrf_token', $token->value());
        $session->set('csrf_time', time());

        return $token->value();
    }

    /**
     * Check if csrf token is equal to stored csrf token
     * @return bool
     */
    public function tokenIsValid(): bool
    {
        $storedToken = $this->session->get('csrf_token');

        return $this->token === $storedToken;
    }

    /**
     * Check age of csrf token, destroy if to old
     * @return bool
     */
    public function tokenIsRecent(): bool
    {
        $maxElapsed = 60 * 60 * 24; // 1 day
        $csrfTime = $this->session->get('csrf_time');

        if ($csrfTime) {
            $storedTime = $csrfTime;

            return ($storedTime + $maxElapsed) >= time();
        }

        $this->destroyToken();

        return false;
    }

    /**
     * Destroy session for csrf token and csrf time
     * @return void
     */
    private function destroyToken(): void
    {
        $this->session->remove('csrf_token');
        $this->session->remove('csrf_time');
    }
}
