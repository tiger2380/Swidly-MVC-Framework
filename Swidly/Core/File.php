<?php

declare(strict_types=1);

namespace Swidly\Core;
class File {
    /**
     * @param string $path
     * @return string
     * @throws SwidlyException
     */
    public static function readFile(string $path): string {
        if(file_exists($path)) {
            return file_get_contents($path);
        }
        throw new SwidlyException('Unable to open file.');
    }

    /**
     * @param string $path
     * @return string
     * @throws SwidlyException
     */
    public static function isFile(string $path): bool {
        if(file_exists($path)) {
            return true;
        }
        
        return false;
    }

    /**
     * @param string $path
     * @param string $content
     * @return bool
     * @throws SwidlyException
     */
    public static function putFile(string $path, string $content): bool {
        if(file_put_contents($path, $content)) {
            return true;
        }
        throw new SwidlyException('Unable to open file.');
    }

    /**
     * @param string $path
     * @return mixed
     * @throws SwidlyException
     */
     public static function readJson(string $path): array {
        $content = self::readFile($path);
        return json_decode($content, true);
    }
    
    public static function readArray(string $path): array | self {
        if (is_file($path)) {
            $response = include $path;
            if (is_array($response)) {
                return $response;
            }

            return new self($response);
        }

        throw new SwidlyException('Unable to open file.');
    }

    /**
     * @param string $source
     * @param string $destination
     * @param bool $keepOriginal
     * @return bool
     * @throws SwidlyException
     */
    public static function copyFile(string $source, string $destination, bool $keepOriginal = true): bool {
        $content =  self::readFile($source);
        if(file_put_contents($destination, $content)) {
            if(!$keepOriginal) {
                unlink($source);
            }

            return true;
        }
        throw new SwidlyException('Unable to copy file.');
    }

    public function toJSON() {
        return json_encode($this);
    }

    // covert the object to an array
    public function toArray() {
        return (array) $this;
    }

    // check to see if their a chain method after the call
    // if so, then we need to call the method and pass the result to the next method
    // if not, then we need to return the result
    // if the method is not found, then we need to throw an exception
    public function __call($name, $arguments) {
        dump($name, $arguments);
        if (method_exists($this, $name)) {
            $result = $this->$name(...$arguments);
            if (count($arguments) > 0) {
                $next = $arguments[0];
                if (is_callable($next)) {
                    return $next($result);
                }
            }
            return $result;
        }
        throw new SwidlyException('Method not found.');
    }
}