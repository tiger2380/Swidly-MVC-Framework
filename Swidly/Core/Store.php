<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Swidly\Core;

/**
 * Description of Store
 *
 * @author thadd
 */
class Store {
    static function save($key, $value) {
        if(self::hasKey($key)) {
            self::delete($key);
        }
        $_SESSION[$key] = $value;
    }
    
    static function hasKey($key) {
        return array_key_exists($key, $_SESSION);
    }

    static function showMessage($key) {
        if(self::hasKey($key)) {
            echo $_SESSION[$key];
        } else {
            echo null;
        }

        self::delete($key);
    }
    
    static function get($key, $default = null) {
        if(self::hasKey($key)) {
            return $_SESSION[$key];
        } else {
            return $default;
        }
    }
    
    static function delete($key) {
        if(self::hasKey($key)) {
            unset($_SESSION[$key]);
        }
    }
}
