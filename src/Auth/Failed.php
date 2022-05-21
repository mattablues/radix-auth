<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: Failed.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-21, 10:19
 */

namespace Radix\Auth;

use Radix\Configuration\Config;
use Radix\Model\FailedLogin;
use Radix\Model\User;

/**
 * @property int|null $id
 * @property string $login
 * @property int $count
 * @property string $last_time
 * @property int $blocked
 * Class Failed
 * @package Radix\Auth
 */
class Failed
{
    /**
     * Get login
     * @param  string  $login
     * @return void
     */
    public function login(string $login): void
    {
        $this->login = $login;
    }

    /**
     * Get failed login
     * @return FailedLogin|null
     */
    public function get(): ?FailedLogin
    {
        $failedLogin = new FailedLogin();

        if ($user = $this->registeredUser()) {
            $this->login = $user->username;
        }

        return $failedLogin->find('login', '=', $this->login);
    }

    /**
     * Record failed login
     * @return void
     */
    public function record(): void
    {
        $failedLogin = new FailedLogin();

        if ($user = $this->registeredUser()) {
            $this->id = $user->id;
            $this->login = $user->username;
        }

        if ($hasFailed = $this->get()) {
            $failedLogin->update([
                'count' => $hasFailed->count + 1,
                'last_time' => time(),
                'blocked' => $hasFailed->blocked
            ], 'login', $hasFailed->login);

            return;
        }

        $failedLogin->create([
            'id' => $this->id ?? null,
            'login' => $this->login,
            'count' => $this->count + 1,
            'last_time' => time(),
            'blocked' => 0
        ]);
    }

    /**
     * Clear failed login
     * @param  string  $login
     * @return void
     */
    public function clear(string $login): void
    {
        $failedLogin = new FailedLogin();

        $this->login = $login;

        if ($this->get()) {
            $failedLogin->delete('login', '=', $this->login);
        }
    }

    /**
     * Check if user is blocked
     * @return int
     */
    public function blocked(): int
    {
        return $this->get()->blocked;
    }

    /**
     * Throttle failed login
     * @return int|float
     */
    public function throttle(): int|float
    {
        $failedLogin = new FailedLogin();
        $config = new Config('form');

        $delay = 60 * $config->get('form.throttle.delay');
        $failed = $this->get();

        if ($failed->count === $config->get('form.throttle.block')) {
            $failedLogin->update(['blocked' => 1], 'login', $this->login);

            if($user = $this->registeredUser()) {
                $user->update(['status' => 2], 'username', $this->login);
            }
        }

        if ($failed && $failed->count === $config->get('form.throttle.times')
            || $failed && $failed->count % $config->get('form.throttle.times') === 0) {
            $remainingDelay = ((int) $failed->last_time + $delay) - time();
            $remainingDelayInMinutes = ceil($remainingDelay / 60);

            if ($remainingDelayInMinutes <= 0) {
                return 0;
            }

            return $remainingDelayInMinutes;
        }

        return 0;
    }

    /**
     * Get user if exist
     * @return User|null
     */
    private function registeredUser(): ?User
    {
        $user = new User();

        if (filter_var($this->login, FILTER_VALIDATE_EMAIL)) {
            $user = $user->find('email', '=', $this->login);
        } else {
            $user = $user->find('username', '=', $this->login);
        }

        return $user;
    }
}
