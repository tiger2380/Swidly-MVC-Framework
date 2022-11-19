<?php

declare(strict_types=1);

namespace App\Core;

class Model {
    public $db;
    public $app;
    protected $class;
    private $vars = [];
    protected const ATTRIBUTE_NAME = 'Column';


    public function __construct()
    {
        global $app;
        $this->class = get_called_class();
        $this->app = $app;
        $this->result = null;
    }

    function find(array $criteria) {
        $result = DB::Table($this->table)->Select()->WhereOnce($criteria);
        $class = new $this->class();

        foreach ($result as $column => $variable) {
            if ($column === $this->idField) {
                $class->{$column} = $variable;
            } else {
                if(method_exists($class, 'set'.ucfirst($column))) {
                    $class->{'set'.ucfirst($column)}($variable);
                } else {
                    throw new \App\Core\AppException('Missing method: '.'set'.ucfirst($column), 500);
                }
            }
        }

        $class->{'doUpdate'} = true;

        return $class;
    }

    function findAll() {
        $result = DB::Table($this->table)->Select()->All();
        $results = [];

        foreach ($result as $key => $value) {
            $class = new $this->class();
            foreach ($value as $column => $variable) {
                if ($column === $this->idField) {
                    $class->id = $variable;
                } else {
                    if(method_exists($class, 'set'.ucfirst($column))) {
                        $class->{'set'.ucfirst($column)}($variable);
                    } else {
                        throw new \App\Core\AppException('Missing method: '.'set'.ucfirst($column), 500);
                    }
                }
            }
            $results[] = $class;
        }

        $class->{'doUpdate'} = true;
        
        return $results;
    }

    function save() {
        $entity = $this->table;
        $data = [];
        
        $props = $this->getProperty( new \ReflectionClass($this));

        foreach ($props as $key => $prop) {
            $data[$key] = $this->{$prop}();
        }

        if($data[$this->idField] === 0) {
            DB::Table($entity)->Insert($data);
        } else {
            DB::Table($entity)->Update($data)->WhereOnce([$this->idField => $data[$this->idField]]);
        }

        return true;
    }

    public  function getProperty(\ReflectionClass $reflectionClass)
    {
        $data = [];
        $reflectionProperties = $reflectionClass->getProperties();
        foreach ($reflectionProperties as $reflectionProperty) {
            $attributes = $reflectionProperty->getAttributes(Column::class);

            foreach ($attributes as $attribute) {
                $name = $reflectionProperty->getName() ?? '';
                $data[$name] = 'get'.ucfirst($name);
            }
        }

        return $data;
    }

    public function __set($name, $value) {
        $this->vars[$name] = $value;
    }

    public function __get($name) {
        if (array_key_exists($name, $this->vars)) {
            return $this->vars[$name];
        }
        return null;
    }
}