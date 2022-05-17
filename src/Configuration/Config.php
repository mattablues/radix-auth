<?php

declare(strict_types=1);

/**
 * Project name: radix-config
 * Filename: Config.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-10, 14:17
 */

namespace Radix\Configuration;

use Radix\Configuration\Exception\ConfigException;

class Config
{
    private string $path;
    private string $directory = 'config';
    private string $files;
    private array $content;

    /**
     * Config Constructor
     * @param  string|null  $files
     * @param  string|null  $directory
     * @throws ConfigException
     */
    public function __construct(string $files = null, string $directory = null)
    {
        if ($files) {
            $this->files = $files;

            if ($directory) {
                $this->directory = $directory;
            }

            $this->path = dirname(__DIR__, 2).'/'.$this->directory.'/';
            $this->content = $this->getContent();
        }
    }

    /**
     * Make Config
     * @param  string  $files
     * @param  string|null  $directory
     * @return Config
     * @throws ConfigException
     */
    public static function make(string $files, string $directory = null): Config
    {
        return new static($files, $directory);
    }

    /**
     * Get config
     * @param  int|string|null  $key
     * @return mixed
     */
    public function get(int|string|null $key = null): mixed
    {
        if ($key) {
            return $this->content[$key];
        }

        return $this->content;
    }

    /**
     * Environment variables
     * @param  string  $key
     * @return mixed
     */
    public function env(string $key): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        return null;
    }

    /**
     * Set environment variable
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    public function setenv(string $key, string $value): void
    {
        $_ENV[$key] = $value;
    }

    /**
     * Get configurations content
     * @return array
     * @throws ConfigException
     */
    private function getContent(): array
    {
        $this->content = [];
        $files = explode('.', $this->files);

        foreach ($files as $field) {
            $path = $this->path . $field . '.php';

            if (file_exists($path)) {
                $file = require $this->path . "$field.php";

                if(!is_array($file) || empty($file)) {
                    throw ConfigException::fileNotFound($field);
                }

                foreach ($file as $key => $value) {
                    if (array_key_exists($key, $this->content)) {
                        throw ConfigException::arrayKeyAlreadyExist($key);
                    }

                    if (is_array($value)) {
                        $newKey = $field . '.' . $key;

                        for ($i = 0; $i < count($value); $i++) {
                            $newKey = $field.'.' . $key . '.' . array_keys($value)[$i];

                            $this->content[$newKey] = array_values($value)[$i];
                        }
                    } else {
                        if (!is_int($key)) {
                            $key = $field . '.' . $key;
                        }

                        $this->content[$key] = $value;
                    }
                }
            } else {
                throw ConfigException::fileNotFound($files);
            }
        }

        return $this->content;
    }
}
