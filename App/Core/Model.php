<?php

namespace App\Core;

class Model {
    public $db;
    public $app;
    protected $class;

    public function __construct()
    {
        global $app;
        $this->class = get_called_class();
        $this->app = $app;
        $this->result = null;
    }

    function find(array $criteria) {
        $this->result = DB::Table($this->table)->Select()->WhereOnce($criteria);
        return $this;
    }

    function findAll() {
        $this->result = DB::Table($this->table)->Select()->run();
        return $this;
    }
}