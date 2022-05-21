<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: index.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-17, 14:06
 */

use Dotenv\Dotenv;
use Radix\Auth\Account;
use Radix\Configuration\Server;
use Radix\Session\Session;

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

$account = new Account();
/*$account->forgotPassword(['email' => 'mats@akebrands.se']);*/

/*$account->update(['id' => 2, 'visible' => 'off']);*/

/*$account->save(['username' => 'mats', 'email' => 'mats@akebrands.se', 'password' => 'korvar65', 'password_repeat' => 'korvar65']);*/
