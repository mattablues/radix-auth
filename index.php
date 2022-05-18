<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: index.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-17, 14:06
 */

use Dotenv\Dotenv;
use Radix\Auth\Auth;
use Radix\Configuration\Server;
use Radix\Session\Session;
use Radix\Utilities\Prep;

require __DIR__ . '/support/helpers.php';
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$server = new Server();
$server->setIni();
$server->setSessionHandler();
$server->setErrorHandler();

$session = new Session();
$session->start();
$session->set('active', time());

$auth = new Auth();

/*var_dump($auth->login(['login' => 'admin', 'password' => 'secret', 'remember_me' => 'on']));
var_dump($auth->revalidate(['login' => 'admin', 'password' => 'secret']));*/
