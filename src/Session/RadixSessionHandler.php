<?php

declare(strict_types=1);

/**
 * Project name: radix-session
 * Filename: RadixSessionHandler.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-15, 15:27
 */

namespace Radix\Session;

use PDO;
use PDOException;
use PDOStatement;
use Radix\Database\DatabaseConnection;
use SessionHandlerInterface;

/**
 * Class RadixSessionHandler
 * @package Radix\Session
 */
class RadixSessionHandler implements SessionHandlerInterface
{
    protected PDO $db;
    protected bool $useTransactions;
    protected int $expires;
    protected array $unlockStatements = [];
    protected bool $collectGarbage = false;

    /**
     * Constructor
     * @param  DatabaseConnection  $connection
     * @param  bool  $useTransactions
     */
    public function __construct(DatabaseConnection $connection, bool $useTransactions = true)
    {
        $this->db = $connection->get();

        if ($this->db->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        $this->db->setAttribute(PDO::ATTR_ORACLE_NULLS, false);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $this->useTransactions = $useTransactions;
        $this->expires = time() + (int) ini_get('session.gc_maxlifetime');
    }

    /**
     * Open the session
     * @param  string  $path
     * @param  string  $name
     * @return bool
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Read session data
     * @param  string  $id
     * @return string
     */
    public function read(string $id): string
    {
        try {
            if ($this->useTransactions) {
                $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
                $this->db->beginTransaction();
            } else {
                $this->unlockStatements[] = $this->getLock($id);
            }

            $sql = 'SELECT expiry, data FROM sessions WHERE id = :id';

            if ($this->useTransactions) {
                $sql .= ' FOR UPDATE';
            }

            $selectStmt = $this->db->prepare($sql);
            $selectStmt->bindParam(':id', $id);
            $selectStmt->execute();

            $result = $selectStmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                if ($result['expiry'] < time()) {
                    return '';
                }

                return $result['data'];
            }

            if ($this->useTransactions) {
                $this->initializeRecord($selectStmt);
            }

            return '';
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Write session data to database
     * @param  string  $id
     * @param  string  $data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        try {
            $sql = 'INSERT INTO sessions (id, expiry, data) 
                        VALUES (:id, :expiry, :data) 
                        ON DUPLICATE KEY UPDATE 
                            expiry = :expiry,
                            data = :data';

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':expiry', $this->expires, PDO::PARAM_INT);
            $stmt->bindParam(':data', $data);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Close session and write session data to database
     * @return bool
     */
    public function close(): bool
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        } elseif ($this->unlockStatements) {
            while ($unlockStmt = array_shift($this->unlockStatements)) {
                $unlockStmt->execute();
            }
        }

        if ($this->collectGarbage) {
            $sql = 'DELETE FROM sessions WHERE expiry < :time';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':time', time(), PDO::PARAM_INT);
            $stmt->execute();

            $this->collectGarbage = false;
        }

        return true;
    }

    /**
     * Destroy session and data
     * @param  string  $id
     * @return bool
     */
    public function destroy(string $id): bool
    {
        $sql = 'DELETE FROM sessions WHERE id = :id';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }

        return true;
    }

    /**
     * Garbage collection
     * @param  int  $max_lifetime
     * @return bool
     */
    public function gc(int $max_lifetime): bool
    {
        $this->collectGarbage = true;

        return true;
    }

    /**
     * Executes on application level, lock on the database
     * @param  string  $id
     * @return PDOStatement
     */
    protected function getLock(string $id): PDOStatement
    {
        $stmt = $this->db->prepare('SELECT GET_LOCK(:key, 50)');
        $stmt->bindValue(':key', $id);
        $stmt->execute();

        $releaseStmt = $this->db->prepare('DO RELEASE_LOCK(:key)');
        $releaseStmt->bindValue(':key', $id);
        $releaseStmt->execute();

        return $releaseStmt;
    }

    /**
     * Account new session ID in database when using transactions
     * @param  PDOStatement  $selectStmt
     * @return string
     */
    protected function initializeRecord(PDOStatement $selectStmt): string
    {
        try {
            $sql = 'INSERT INTO sessions (id, expiry, data) 
                        VALUES (:id, :expiry, :data)';

            $insertStmt = $this->db->prepare($sql);
            $insertStmt->bindParam(':id', $id);
            $insertStmt->bindParam(':expiry', $this->expires, PDO::PARAM_INT);
            $insertStmt->bindValue(':data', '');
            $insertStmt->execute();

            return '';
        } catch (PDOException $e) {
            if (str_starts_with($e->getCode(), '23')) {
                $selectStmt->execute();
                $result = $selectStmt->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    return $result['data'];
                }

                return '';
            }

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
