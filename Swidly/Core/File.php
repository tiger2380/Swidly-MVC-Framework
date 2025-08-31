<?php

declare(strict_types=1);

namespace Swidly\Core;

class File {
    /**
     * Maximum file size in bytes (100MB)
     */
    private const MAX_FILE_SIZE = 104857600;

    /**
     * Reads a file safely with size validation
     * @param string $path Absolute path to file
     * @return string
     * @throws SwidlyException
     */
    public static function readFile(string $path): string {
        $realPath = realpath($path);
        if ($realPath === false || !is_readable($realPath)) {
            throw new SwidlyException('File does not exist or is not readable.');
        }

        if (filesize($realPath) > self::MAX_FILE_SIZE) {
            throw new SwidlyException('File exceeds maximum allowed size.');
        }

        $content = file_get_contents($realPath);
        if ($content === false) {
            throw new SwidlyException('Failed to read file contents.');
        }

        return $content;
    }

    /**
     * Checks if path is a valid file
     * @param string $path Absolute path to file
     * @return bool
     */
    public static function isFile(string $path): bool {
        $realPath = realpath($path);
        return ($realPath !== false && is_file($realPath) && is_readable($realPath));
    }

    /**
     * Writes content to file safely
     * @param string $path Absolute path to file
     * @param string $content Content to write
     * @return bool
     * @throws SwidlyException
     */
    public static function putFile(string $path, string $content): bool {
        $directory = dirname($path);
        
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new SwidlyException('Directory does not exist or is not writable.');
        }

        if (file_exists($path) && !is_writable($path)) {
            throw new SwidlyException('File exists but is not writable.');
        }

        $result = file_put_contents($path, $content, LOCK_EX);
        if ($result === false) {
            throw new SwidlyException('Failed to write file contents.');
        }

        return true;
    }

    /**
     * Reads and decodes JSON file
     * @param string $path Absolute path to file
     * @return array
     * @throws SwidlyException
     */
    public static function readJson(string $path): array {
        $content = self::readFile($path);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SwidlyException('Invalid JSON format: ' . json_last_error_msg());
        }

        return $data;
    }
    
    /**
     * Reads PHP array from file
     * @param string $path Absolute path to file
     * @return array|self
     * @throws SwidlyException
     */
    public static function readArray(string $path): array|self {
        if (!self::isFile($path)) {
            throw new SwidlyException('File does not exist or is not readable.');
        }

        try {
            $response = include $path;
        } catch (\Throwable $e) {
            throw new SwidlyException('Failed to include file: ' . $e->getMessage());
        }

        if (is_array($response)) {
            return $response;
        }

        if (is_object($response)) {
            return new self($response);
        }

        throw new SwidlyException('File did not return an array or object.');
    }

    /**
     * Copies file with validation
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @param bool $keepOriginal Whether to keep the original file
     * @return bool
     * @throws SwidlyException
     */
    public static function copyFile(string $source, string $destination, bool $keepOriginal = true): bool {
        if (!self::isFile($source)) {
            throw new SwidlyException('Source file does not exist or is not readable.');
        }

        $destDir = dirname($destination);
        if (!is_dir($destDir) || !is_writable($destDir)) {
            throw new SwidlyException('Destination directory does not exist or is not writable.');
        }

        if (file_exists($destination) && !is_writable($destination)) {
            throw new SwidlyException('Destination file exists but is not writable.');
        }

        $content = self::readFile($source);
        if (!self::putFile($destination, $content)) {
            throw new SwidlyException('Failed to copy file.');
        }

        if (!$keepOriginal) {
            if (!unlink($source)) {
                throw new SwidlyException('Failed to remove original file.');
            }
        }

        return true;
    }

    /**
     * Converts object to JSON
     * @return string
     * @throws SwidlyException
     */
    public function toJSON(): string {
        $json = json_encode($this, JSON_THROW_ON_ERROR);
        if ($json === false) {
            throw new SwidlyException('Failed to encode object to JSON.');
        }
        return $json;
    }

    /**
     * Converts object to array
     * @return array
     */
    public function toArray(): array {
        return (array) $this;
    }

    /**
     * Handles method chaining
     * @param string $name Method name
     * @param array $arguments Method arguments
     * @return mixed
     * @throws SwidlyException
     */
    public function __call(string $name, array $arguments): mixed {
        if (!method_exists($this, $name)) {
            throw new SwidlyException("Method '$name' not found.");
        }

        $result = $this->$name(...$arguments);

        if (!empty($arguments) && is_callable($arguments[0])) {
            return $arguments[0]($result);
        }

        return $result;
    }
}