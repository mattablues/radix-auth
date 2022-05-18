<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: PersistentSessionHandler.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-18, 11:11
 */

namespace Radix\Session;

use PDO;
use PDOException;
use Radix\Configuration\Config;
use Radix\Database\DatabaseConnection;

class PersistentSessionHandler extends RadixSessionHandler
{
    private string $sessionUserKey;
    private string $sessionCookieName;
    private string $sessionPersist;
    private string $sessionUsername;
    private Session $session;
    private Config $config;

    /**
     * Constructor
     * @param  DatabaseConnection  $connection
     * @param  bool  $useTransactions
     */
    public function __construct(DatabaseConnection $connection, bool $useTransactions = true)
    {
        $this->config = new Config();

        $this->sessionUserKey = $this->config->env('SESSION_USER_KEY');
        $this->sessionCookieName = $this->config->env('SESSION_COOKIE_NAME');
        $this->sessionPersist = $this->config->env('SESSION_PERSIST');
        $this->sessionUsername = $this->config->env('SESSION_USERNAME');

        parent::__construct($connection, $useTransactions);
    }

    /**
     * Writes the session data to the database
     * @param  string  $id
     * @param  string  $data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        $this->session = new Session();
        $this->session->start();
        $sessionPersist = $this->session->get($this->sessionPersist);
        $sessionCookieName = $this->session->get($this->sessionCookieName);

        try {
            $sql = "INSERT INTO sessions (id, expiry, data) VALUES (:id, :expiry, :data)
                    ON DUPLICATE KEY UPDATE expiry = :expiry, data = :data";

            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':expiry', $this->expires, PDO::PARAM_INT);
            $stmt->bindParam(':data', $data);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if (isset($sessionPersist) || isset($sessionCookieName)) {
                $this->storeAutologinData($data);
            }

            return true;

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }

            throw $e;
        }
    }

    /**
     * Copies the user's session data to the autologin table
     * @param  string  $data
     * @return void
     */
    protected function storeAutologinData(string $data): void
    {
        $this->session = new Session();
        $this->session->start();
        $sessionUserKey = $this->session->get($this->sessionUserKey);
        $sessionUsername = $this->session->get($this->sessionUsername);

        // Get the user key if it's not already stored as a session variable
        if (!isset($this->sessionUserKey)) {
            $sql = "SELECT user_key FROM users
                    WHERE username = :username";


            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':username', $sessionUsername);
            $stmt->execute();

            $this->session->set($this->sessionUserKey, $stmt->fetchColumn());
        }
        // Copy the session data to the autologin table
        $sql = "UPDATE autologin
                SET data = :data WHERE user_key = :key";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':key', $sessionUserKey);
        $stmt->execute();
    }
}
