<?php

declare(strict_types=1);

/**
 * Project name: radix-session
 * Filename: Server.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-15, 15:34
 */

namespace Radix\Configuration;

use Radix\Database\DatabaseConnection;
use Radix\Session\RadixSessionHandler;

/**
 * Class Server
 * @package Radix\Configuration
 */
class Server
{
    /**
     * Set ini settings
     * @return void
     */
    public function setIni(): void
    {
        ini_set('default_charset', env('APP_CHARSET'));
        ini_set('date.timezone', env('APP_TIMEZONE'));
        ini_set('display_errors', 'off');
        ini_set('session.auto_start', '0');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cache_limiter', 'nocache');
        ini_set('session.name', env('SESSION_NAME'));
        ini_set('session.cookie_domain', '');
        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_lifetime', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '0');
        ini_set('session.gc_maxlifetime', '1440');
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
    }

    /**
     * Set session handler
     * @return void
     */
    public function setSessionHandler(): void
    {
        $connection = new DatabaseConnection();
        $handler = new RadixSessionHandler($connection);
        session_set_save_handler($handler);
    }

    public function setErrorHandler(): void
    {

    }
}
