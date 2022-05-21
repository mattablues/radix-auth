<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: Account.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-21, 12:23
 */

namespace Radix\Auth;

use Exception;
use Radix\Model\User;
use Radix\Utilities\Prep;
use Radix\Utilities\Token;
use Radix\Validator\Validator;

/**
 * Class Account
 * @package Radix\Auth
 */
class Account
{
    private array $errors = [];
    private string $token;
    private ?User $user = null;

    /**
     * Save new user
     * @param  array  $data
     * @return bool
     */
    public function save(array $data): bool
    {
        $validator = new Validator($data);
        $validator->setUniqueTable('users');

        $validator->rules('username', 'required|space|unique|max:8|min:4|let:3');
        $validator->rules('email', 'required|space|unique|email');
        $validator->rules('password', 'required|space|num:2|let:2|min:8|max:15');
        $validator->rules('password_repeat', 'required|match:password');

        if ($validator->validate()) {
            $failedLogin = new Failed();
            $failedLogin->clear($data['username']);
            $failedLogin->clear($data['email']);

            $token = new Token();
            $this->user = new User();
            $this->token = $token->value();

            $this->user->create([
                'user_key' => Prep::hash($data['username']),
                'username' => strtolower($data['username']),
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'activation' => $token->hash(),
                'role' => 'user',
                'status' => 0,
                'visible' => 'off',
            ]);

            // Send activation email

            return true;
        }

        $this->errors = $validator->hasErrors();

        return false;
    }

    /**
     * Update user
     * @param  array  $data
     * @return bool
     */
    public function update(array $data): bool
    {
        $validator = new Validator($data);
        $validator->setUniqueTable('users');

        $validator->rules('username', 'required|space|unique|max:8|min:4|let:3');
        $validator->rules('email', 'required|space|unique:except|email');
        $validator->rules('password', 'required:not|space|num:2|let:2|min:8|max:15');
        $validator->rules('password_repeat', 'required:not|match:password');

        if ($validator->validate()) {
            $this->user = new User();

            $updateData = [
                'email' => $data['email'],
                'visible' =>  $data['visible'] ?? 'off',
                'updated_at' => date('Y-m-d H:i:s', time()),
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $this->user->update($updateData, 'id', $data['id']);

            return true;
        }

        $this->errors = $validator->hasErrors();

        return false;
    }

    /**
     * Activate user
     * @param  string  $token
     * @return int|null
     */
    public function activate(string $token): ?int
    {
        $this->user = new User();
        return $this->user->update(['status' => 1, 'activation' => null], 'activation', $token);
    }

    /**
     * Forgot password
     * @param  array  $data
     * @return bool
     */
    public function forgotPassword(array $data): bool
    {
        $validator = new Validator($data);
        $validator->rules('email', 'required|space|email');

        if ($validator->validate()) {
            $user = new User();
            $this->user = $user->find('email', '=', $data['email']);

            if($this->user) {
                if ($this->user->status === 1) {
                    $token = new Token();

                    $this->user->update([
                        'password_reset' => $token->hash(),
                        'password_reset_expires_at' => date('Y-m-d H:i:s', time() + 60 * 60 * 2),
                    ], 'email', $this->user->email);

                    $this->token = $token->value();


                    // Send Password reset email

                }

                return true;
            }
        }

        $this->errors = $validator->hasErrors();

        return false;
    }

    /**
     * Reset password
     * @param  array  $data
     * @return bool
     * @throws Exception
     */
    public function resetPassword(array $data): bool
    {
        $validator = new Validator($data);
        $validator->rules('password', 'required|space|num:2|char:2|min:8|max:15');
        $validator->rules('password_repeat', 'required|match:password');

        if ($validator->validate()) {
            $token = new Token($data['token']);
            $hashedToken = $token->hash();

            $user = new User();
            $user->update([
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'password_reset' => null,
                'password_reset_expires_at' => null,
            ], 'password_reset', $hashedToken);

            return true;
        }

        return false;
    }

    /**
     * Get errors
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
