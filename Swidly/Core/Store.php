<?php

namespace Swidly\Core;

/**
 * The Store class is a PHP class that provides methods for handling sessions. 
 * It has static methods for starting a session, saving a value to the session, retrieving a CSRF token, and checking if a key exists in the session. 
 * The class is part of the Swidly\Core namespace.
 *
 * @author thadd
 */
class Store {
    // start session
    static function start(): void
    {
        session_start();
    }

    // save session
    static function save($key, $value): void
    {
        // check to see if session has started
        if(!isset($_SESSION)) {
            self::start();
        }
        
        if(self::hasKey($key)) {
            self::delete($key);
        }
        $_SESSION[$key] = $value;
    }

    // csrf token
    static function csrf(): string
    {
        // check to see if session has started
        if(!isset($_SESSION)) {
            self::start();
        }
        
        if(self::hasKey('csrf')) {
            return $_SESSION['csrf'];
        } else {
            $csrf = bin2hex(random_bytes(32));
            self::save('csrf', $csrf);
            return $csrf;
        }
    }

    // verify csrf token
    static function verifyCsrf($token): bool
    {
        if(self::hasKey('csrf')) {
            return hash_equals($_SESSION['csrf'], $token);
        } else {
            return false;
        }
    }

    // regenerate csrf token
    static function regenerateCsrf(): void
    {
        $csrf = bin2hex(random_bytes(32));
        self::save('csrf', $csrf);
    }

    // delete csrf token
    static function deleteCsrf(): void
    {
        self::delete('csrf');
    }

    // copy session to another session
    static function copy($key, $newKey): void
    {
        if(self::hasKey($key)) {
            self::save($newKey, $_SESSION[$key]);
        }
    }

    // check if session has key    
    static function hasKey($key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    // flash message
    static function flashMessage($key): void
    {
        if(self::hasKey($key)) {
            echo $_SESSION[$key];
        } else {
            echo null;
        }

        self::delete($key);
    }
    
    // get session
    static function get($key, $default = null) {
        if(self::hasKey($key)) {
            return $_SESSION[$key];
        } else {
            return $default;
        }
    }
    
    // delete session
    static function delete($key): void
    {
        if(self::hasKey($key)) {
            unset($_SESSION[$key]);
        }
    }
}