<?php

declare(strict_types=1);

/**
 * Project name: radix-session
 * Filename: Flash.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-15, 17:30
 */

namespace Radix\Session\Flash;

use Radix\Session\Exception\FlashException;
use Radix\Session\Session;

/**
 * Class Flash
 * @package Radix\Session\Flash
 */
class Flash
{
    private Session $session;
    private const TYPES = [
        'success', 'info', 'warning', 'error', 'enlightenment',
    ];

    /**
     * Flash Constructor
     * @param  Session  $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->session->start();
    }

    /**
     * Set flash message
     * @param  string  $message
     * @param  string  $type
     * @return void
     * @throws FlashException
     */
    public function setMessage(string $message, string $type = 'success'): void
    {
        if (!in_array($type, self::TYPES)) {
            throw FlashException::messageTypeNotAllowed($type);
        }

        if ($this->session->get('flash_notifications') === null) {
            $this->session->set('flash_notifications', []);
        }

        $this->session->set('flash_notification', ['body' => $message, 'type' => $type]);
    }

    /**
     * Get message
     * @return mixed|null
     */
    public function getMessage(): ?array
    {
        $notification = $this->session->get('flash_notification');

        if (isset($notification)) {
            $message = $notification;

            $this->session->remove('flash_notification');

            return $message;
        }

        return null;
    }
}
