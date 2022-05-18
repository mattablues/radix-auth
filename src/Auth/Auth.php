<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: Auth.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-17, 14:32
 */

namespace Radix\Auth;

use JetBrains\PhpStorm\NoReturn;
use Radix\Configuration\Config;
use Radix\Model\User;
use Radix\Session\Session;
use Radix\Validator\Validator;

/**
 * Class Auth
 * @package Radix\Auth
 */
class Auth
{
    private Config $config;
    private Session $session;
    private array $errors = [];

    /**
     * Auth constructor
     */
    public function __construct()
    {
        $this->session = new Session();
        $this->session->start();
        $this->config = new Config('rule.form.email');
    }

    /**
     * Validate and login user
     * @param  array  $data
     * @return bool
     */
    public function login(array $data): bool
    {
        $validator = new Validator($data);

        $validator->rules('login', 'required');
        $validator->rules('password', 'required');

        $user = $this->authenticate($data);

        if ($validator->validate()) {
            if ($user) {
                session_regenerate_id(true);

                $this->session->set('username', $user->username);
                $this->session->set('authenticated', true);
                $this->session->set('ip', $_SERVER['REMOTE_ADDR']);
                $this->session->set('user_agent', $_SERVER['HTTP_USER_AGENT']);
                $this->session->set('last_login', time());

                $this->confirmIsValid();

                $rememberMe = $data['remember_me'] ?? 'off';

                if ($rememberMe === 'on' && $user->role !== 'admin') {
                    $autologin = new Autologin();
                    $autologin->persistentLogin();
                }

                return true;
            }
        }

        $this->errors = $validator->hasErrors();

        return false;
    }

    public function revalidate(array $data): bool
    {
        $validator = new Validator($data);

        $validator->rules('username', 'required');
        $validator->rules('password', 'required');

        $validator->validate();

        $sessionInvalid = $this->session->get('invalid');

        if (!$sessionInvalid) {
            $this->session->set('invalid', 0);
        }

        $user = $this->authenticate($data);

        if (!$user && !empty($data['username']) && !empty($data['password'])) {
            $validator->addError('form-error', $this->config->get('rule.add.revalidate'));
            $sessionInvalid++;
            $this->session->set('invalid', $sessionInvalid);
        }

        if($this->session->get('invalid') >= $this->config->get('form.revalidate.max')) {
            $remember = $this->session->get($this->config->env('SESSION_PERSIST'));
            $cookie = $this->session->get($this->config->env('SESSION_COOKIE_NAME'));

            if (isset($remember) || isset($cookie)) {
                $autologin = new Autologin();
                $autologin->logout();
            }

            $this->logout();


            // Redirect
        }

        if ($user && $validator->validate()) {
            session_regenerate_id(true);
            $this->session->set('revalidated', true);

            return true;
        }

        $this->errors = $validator->hasErrors();

        return false;
    }

    /**
     * Get user
     * @return User|null
     */
    public function user(): ?User
    {
        if ($this->isLoggedIn()) {
            $user = new User();
            $user->find('username', '=', $this->session->get('username'));

            return $user;
        }

        return null;
    }

    /**
     * Check if user is admin
     * @return bool
     */
    public function userIsAdmin(): bool
    {
        $user = $this->user();

        if ($user) {
            if ($user->role === 'admin') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        $sessionAuthenticated = $this->session->get('authenticated');
        $sessionCookie = $this->session->get($this->config->env('SESSION_COOKIE_NAME'));

        if (isset($sessionAuthenticated) || isset($sessionCookie)) {
            return true;
        }

        $autologin = new Autologin();
        $autologin->checkCredentials();
        $cookie = $this->session->get($this->config->env('SESSION_COOKIE_NAME'));

        if (isset($cookie)) {
            return true;
        }

        return false;
    }

    /**
     * Errors
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Logout
     * @return void
     */
    public function logout(): void
    {
        $_SESSION = [];

        if(ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 86400,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Logout if account is closed
     * @return void
     */
    #[NoReturn] public function logoutClosedAccount(): void
    {
        $this->clearAutologin();
        $this->logout();

        // Redirect
    }

    /**
     * Remember requested page
     * @return void
     */
    public function rememberRequestedPage(): void
    {
        $this->session->set('return_to', $_SERVER['REQUEST_URI']);
    }

    /**
     * Return to page
     * @return string
     */
    public function returnToPage(): string
    {
        //return $this->session->get('return_to')  ?? $this->config->get('route.user.index');
    }

    /**
     * Authenticate user by login or username
     * @param  array  $data
     * @return User|null
     */
    private function authenticate(array $data): ?User
    {
        $user = new User();

        if (isset($data['login'])) {
            if (filter_var($data['login'], FILTER_VALIDATE_EMAIL)) {
                $user = $user->find('email', '=', $data['login']);
            } else {
                $user = $user->find('username', '=', $data['login']);
            }

            if ($user && password_verify($data['password'], $user->password)) {
                return $user;
            }

        } else if (isset($data['username'])) {
            $user = $user->find('username', '=', $data['username']);

            if ($user && password_verify($data['password'], $user->password)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Confirm sessions are valid
     * @return void
     */
    private function confirmIsValid(): void
    {
        if (!$this->isValid()) {
            $this->logout();

            // Redirect
        }
    }

    /**
     * Check if valid
     * @return bool
     */
    private function isValid(): bool
    {
        if (!$this->ipMatches()) {
            return false;
        }

        if (!$this->userAgentMatches()) {
            return false;
        }

        if (!$this->loginIsRecent()) {
            return false;
        }

        return true;
    }

    /**
     * Check if ip matches
     * @return bool
     */
    private function ipMatches(): bool
    {
        $sessionIp = $this->session->get('ip');

        if (!isset($sessionIp) || !isset($_SERVER['REMOTE_ADDR'])) {
            return false;
        }

        if ($sessionIp === $_SERVER['REMOTE_ADDR']) {
            return true;
        }

        return false;
    }

    /**
     * Check if user agents matches
     * @return bool
     */
    private function userAgentMatches(): bool
    {
        $sessionUserAgent = $this->session->get('user_agent');

        if (!isset($sessionUserAgent) && !isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        if ($sessionUserAgent === $_SERVER['HTTP_USER_AGENT']) {
            return true;
        }

        return false;
    }

    /**
     * Check if login is recent
     * @return bool
     */
    private function loginIsRecent(): bool
    {
        $maxElapsed = 60 * 60 * 24; // 1 day
        $sessionLastLogin = $this->session->get('last_login');

        if (!isset($sessionLastLogin)) {
            return false;
        }

        if (($sessionLastLogin + $maxElapsed) >= time()) {
            return true;
        }

        return false;
    }

    /**
     * Clear autologin
     * @return void
     */
    private function clearAutologin(): void
    {
        $autologin = new Autologin();
        $autologin->logout();
    }
}
