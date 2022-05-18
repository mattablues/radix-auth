<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: Autologin.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-18, 10:57
 */

namespace Radix\Auth;

use PDO;
use PDOException;
use Radix\Configuration\Config;
use Radix\Database\DatabaseConnection;
use Radix\Session\Cookie\Cookie;
use Radix\Session\Session;
use Radix\Utilities\Token;

class Autologin
{
    private PDO $db;
    private Cookie $cookie;
    private Session $session;
    private Config $config;
    private int $tokenIndex;
    private int $lifetimeDays = 30;
    private int $expiry;
    private string $cookiePath = '/';
    private string $domain = '';
    private bool $secure = false;
    private bool $httponly = true;
    private string $sessionAuth = 'authenticated';
    private string $sessionRevalidated = 'revalidated';
    private string $sessionUserKey;
    private string $sessionCookieName;
    private string $sessionPersist;
    private string $sessionUsername;

    /**
     * Autologin Constructor
     * @param  int  $tokenIndex
     */
    public function __construct(int $tokenIndex = 0)
    {
        $this->session = new Session();
        $this->session->start();
        $this->config = new Config();
        $this->cookie = new Cookie();
        $db = new DatabaseConnection();

        $this->sessionUserKey = $this->config->env('SESSION_USER_KEY');
        $this->sessionCookieName = $this->config->env('SESSION_COOKIE_NAME');
        $this->sessionPersist = $this->config->env('SESSION_PERSIST');
        $this->sessionUsername = $this->config->env('SESSION_USERNAME');

        $this->db = $db->get();

        if ($this->db->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        $this->db->setAttribute(PDO::ATTR_ORACLE_NULLS, false);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $this->tokenIndex = ($tokenIndex <= 31) ? $tokenIndex : 31;
        $this->expiry = time() + ($this->lifetimeDays * 60 * 60 * 24);
    }

    /**
     * Creates a persistent login for the user
     * @return void
     */
    public function persistentLogin(): void
    {
        $this->session->set($this->sessionUserKey, $this->getUserKey());
        // Get the user's ID
        if ($this->session->get($this->sessionUserKey)) {
            $this->getExistingData();

            // Generate a random 32-digit hexadecimal token
            $token = $this->generateToken();

            // Store the token and user's ID in the database
            $this->storeToken($token);

            // Store the single-use token as a cookie in the user's browser
            $this->setCookie($token);
            $this->session->set($this->sessionPersist, true);
            $this->session->remove($this->sessionCookieName);
        }
    }

    /**
     * Check if a valid persistent cookie has been presented
     * @return void
     */
    public function checkCredentials(): void
    {
        $cookie = $this->cookie->get($this->sessionCookieName);

        // Do nothing if the cookie doesn't exist
        if (isset($cookie)) {
            if ($storedToken = $this->parseCookie()) {
                // Delete expired tokens before checking the current one
                $this->clearOld();

                // Log in the user if the token hasn't been used
                if ($this->checkCookieToken($storedToken, false)) {
                    // Log in the user
                    $this->cookieLogin($storedToken);
                    // Generate and store a fresh single-use token
                    $newToken = $this->generateToken(); //

                    $this->storeToken($newToken);
                    $this->setCookie($newToken);

                } elseif ($this->checkCookieToken($storedToken, true)) {
                    // If the token has already been used, suspect an attack,
                    // delete all tokens associated with the user key,
                    // and invalidate the current session.
                    $this->deleteAll();
                    $_SESSION = [];
                    $params = session_get_cookie_params();
                    $this->cookie->set(session_name(), '', time() - 86400, $params['path'], $params['domain'],
                        $params['secure'], $params['httponly']);
                    session_destroy();
                    // Invalidate the autologin cookie
                    $this->cookie->set($this->config->env('SESSION_COOKIE_NAME'), '', time() - 86400, $this->cookiePath,
                        $this->domain, $this->secure, $this->httponly);
                }
            }
        }
    }

    /**
     * Logs out the user from all sessions or just the current one
     * @param  bool  $all
     * @return void
     */
    public function logout(bool $all = true): void
    {
        if ($all) {
            $this->deleteAll();
        } else {
            $token = $this->parseCookie();

            $sql = "UPDATE autologin SET used = 1
                    WHERE token = :token";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
        }

        $this->cookie->set($this->sessionCookieName, '', time() - 86400, $this->cookiePath,
            $this->domain, $this->secure, $this->httponly);
    }

    /**
     * Retrieves the user's ID from the users table
     * @return string
     */
    protected function getUserKey(): string
    {
        $sessionUsername = $this->session->get($this->sessionUsername);

        $sql = "SELECT user_key FROM users
                WHERE username = :username";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $sessionUsername);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * Retrieve the user's data from the most recent session
     * @return void
     */
    protected function getExistingData(): void
    {
        $sessionUserKey = $this->session->get($this->sessionUserKey);

        $sql = "SELECT data FROM autologin
                WHERE user_key = :key
                ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':key', $sessionUserKey);
        $stmt->execute();

        // Get the most recent result
        if ($data = $stmt->fetchColumn()) {
            // Populate the $_SESSION super global array
            session_decode($data);
        }

        // Release the database connection for other queries
        $stmt->closeCursor();
    }

    /**
     * Generates a random 32-character string for the single-use token
     * @return string
     */
    protected function generateToken(): string
    {
        $token = new Token();
        return $token->value();
    }

    /**
     * Stores the user's ID and single-use token in the database
     * @param  string  $token  32-character hexadecimal string
     */
    protected function storeToken(string $token): void
    {
        $sessionUserKey = $this->session->get($this->sessionUserKey);

        try {
            $sql = "INSERT INTO autologin (user_key, token)
                    VALUES (:key, :token)";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':key', $sessionUserKey);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Creates and stores autologin cookie in user's browser
     * @param  string  $token
     * @return void
     */
    public function setCookie(string $token): void
    {
        $sessionUsername = $this->session->get($this->sessionUsername);
        $sessionUserKey = $this->session->get($this->sessionUserKey);

        $merged = str_split($token);
        array_splice($merged, hexdec($merged[$this->tokenIndex]), 0, $sessionUserKey);
        $merged = implode('', $merged);

        $token = $sessionUsername. '|' . $merged;

        $this->cookie->set($this->sessionCookieName, $token, $this->expiry,
            $this->cookiePath, $this->domain, $this->secure,
            $this->httponly);
    }

    /**
     * Removes the user_key from the cookie token
     * @return bool|string
     */
    protected function parseCookie(): bool|string
    {
        $sessionCookieName = $this->cookie->get($this->sessionCookieName);

        // Separate the username and submitted token
        if ($sessionCookieName) {
            $parts = explode('|', $sessionCookieName);
            $this->session->set($this->sessionUsername, $parts[0]);
            $token = $parts[1];

            // Proceed only if the username is valid
            $this->session->set($this->sessionUserKey, $this->getUserKey());
            if ($this->session->get($this->sessionUserKey)) {
                // Remove the user's ID from the submitted cookie token
                return str_replace($this->session->get($this->sessionUserKey), '', $token);
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Deletes records older than the value set in the $lifetimeDays property
     * @return void
     */
    protected function clearOld(): void
    {
        $sql = "DELETE FROM autologin
                WHERE DATE_ADD(created_at, INTERVAL :expiry DAY) < NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':expiry', $this->lifetimeDays);
        $stmt->execute();
    }

    /**
     * Checks whether the single-use token has already been used
     * @param  string  $token
     * @param  bool  $used
     * @return bool
     */
    protected function checkCookieToken(string $token, bool $used): bool
    {
        $sessionUserKey = $this->session->get($this->sessionUserKey);

        $sql = "SELECT COUNT(*) FROM autologin
                WHERE user_key = :key AND token = :token
                AND used = :used";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':key', $sessionUserKey);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':used', $used, PDO::PARAM_BOOL);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Delete all entries in autologin table related with the user's ID
     * @return void
     */
    protected function deleteAll(): void
    {
        $sessionUserKey = $this->session->get($this->sessionUserKey);

        $sql = "DELETE FROM autologin WHERE user_key = :key";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':key', $sessionUserKey);
        $stmt->execute();
    }

    /**
     * Cookie login
     * @param  string  $token
     * @return void
     */
    protected function cookieLogin(string $token): void
    {
        $sessionUserKey = $this->session->get($this->sessionUserKey);

        try {
            $this->getExistingData();

            $sql = "UPDATE autologin SET used = 1
                    WHERE user_key = :key AND token = :token";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':key', $sessionUserKey);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            session_regenerate_id(true);

            $this->session->set($this->sessionCookieName, true);
            $this->session->remove($this->sessionAuth);
            $this->session->remove($this->sessionRevalidated);
            $this->session->remove($this->sessionPersist);

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
