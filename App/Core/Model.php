<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Attributes\Column;
use App\Core\Attributes\Table;

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
        $this->reflectionClass = new \ReflectionClass($this->class);

        $this->table = $this->getTableProperty();
        $props = $this->getColumnProperty();
        $this->idField = $props['idField'];
        $this->props = array_diff($props, [$this->idField]);
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

        $idField = $this->idField;

        foreach ($result as $key => $value) {
            $class = new $this->class();
            foreach ($value as $column => $variable) {
                if ($column === $idField) {
                    $class->id = $variable;
                } else {
                    if(method_exists($class, 'set'.ucfirst($column))) {
                        $class->{'set'.ucfirst($column)}($variable);
                    } else {
                        throw new \App\Core\AppException('Missing method: '.'set'.ucfirst($column), 500);
                    }
                }
            }
            
            $class->{'doUpdate'} = true;
            $results[] = $class;
        }
        
        return $results;
    }

    function save() {
        $entity = $this->table;
        $data = [];
       
        $idField = $this->idField;

        foreach ($this->props as $key => $prop) {
            $data[$key] = $this->{$prop}();
        }

        if((int) $data[$idField] === 0) {
            DB::Table($entity)->Insert($data);
        } else {
            DB::Table($entity)->Update($data)->WhereOnce([$idField => $data[$this->idField]]);
        }

        return true;
    }

    public function getColumnProperty()
    {
        $data = [];
        $reflectionProperties = $this->reflectionClass->getProperties();

        foreach ($reflectionProperties as $reflectionProperty) {
            $attributes = $reflectionProperty->getAttributes(Column::class);

            foreach ($attributes as $attribute) {
                $name = $reflectionProperty->getName() ?? '';
                $instance = $attribute->newInstance();
                if($instance->isPrimary) {
                    $data['idField'] = $name;
                }
                $data[$name] = 'get'.ucfirst($name);
            }
        }

        return $data;
    }

    public function getTableProperty() {
        $class = $this->reflectionClass;
        $attribute = $class->getAttributes(Table::class)[0];
        
        if ($attribute) {
            $instance = $attribute->newInstance();

            return $instance->name;
        }
        return null;
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