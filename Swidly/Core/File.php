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
     * @return mixed
     * @throws SwidlyException
     */
    public static function readJson(string $path): string {
        $content = self::readFile($path);
        return json_decode($content, true);
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
}