<?php

namespace Swidly\Core;

class Container {
    protected $container = [];

    public function set($key, $value) {
        $this->container[$key] = $value;
    }

    public function get($key) {
        return $this->container[$key];
    }

    public function has($key) {
        return isset($this->container[$key]);
    }

    public function remove($key) {
        unset($this->container[$key]);
    }

    public function clear() {
        $this->container = [];
    }

    public function __set($key, $value) {
        $this->set($key, $value);
    }

    public function &__get($key) {
        return $this->get($key);
    }

    public function __isset($key) {
        return $this->has($key);
    }

    public function __unset($key) {
        $this->remove($key);
    }

    public function __call($name, $arguments) {
        if (isset($this->container[$name])) {
            return call_user_func_array($this->container[$name], $arguments);
        }
    }

    public function __toString() {
        return json_encode($this->container);
    }

    public function __debugInfo() {
        return $this->container;
    }
}