<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: FailedLogin.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-21, 10:06
 */

namespace Radix\Model;

/**
 * @property int|null $id
 * @property string $login
 * @property int $count
 * @property string $last_time
 * @property int $blocked
 * Class FailedLogin
 * @package Radix\Model
 */
class FailedLogin extends Model
{
    protected string $table = 'failed_logins';
}
