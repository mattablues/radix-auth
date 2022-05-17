<?php

declare(strict_types=1);

/**
 * Project name: radix-model
 * Filename: User.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-13, 14:43
 */

namespace Radix\Model;

/**
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string $created_at
 * Class User
 * @package Radix\Model
 */
class User extends Model
{
    protected string $table = 'users';
}
