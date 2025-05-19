<?php

namespace Swidly\Core\Database;

class SqlBuilder 
{
    private string $tableName;
    private array $columns = [];

    public function __construct(string $tableName) 
    {
        $this->tableName = $tableName;
    }

    public function addColumn(\ReflectionProperty $property, $columnAttr): void 
    {
        $name = $property->getName();
        $type = $columnAttr->type->value;
        $length = $columnAttr->length ?? ($type === 'varchar' ? 255 : null);
        $nullable = $columnAttr->nullable ?? false;
        $isPrimary = $columnAttr->isPrimary ?? false;
        $default = $columnAttr->default ?? 'NULL';

        $this->columns[$name] = [
            'type' => $type,
            'length' => $length,
            'nullable' => $nullable,
            'isPrimary' => $isPrimary,
            'default' => $default
        ];
    }

    public function getCreateTableSql(): string 
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (";
        $columnDefinitions = [];

        foreach ($this->columns as $name => $column) {
            $columnDefinitions[] = $this->buildColumnDefinition($name, $column);
        }

        $sql .= implode(', ', $columnDefinitions) . ')';
        return '$this->addSql(\'' . $sql . "');\n";
    }

    public function getDropTableSql(): string 
    {
        return '$this->addSql(\'DROP TABLE IF EXISTS ' . $this->tableName . "');\n";
    }

    private function buildColumnDefinition(string $name, array $column): string 
    {
        $parts = [
            $name,
            strtoupper($column['type']),
            $column['length'] ? "({$column['length']})" : '',
            $column['nullable'] ? "DEFAULT {$column['default']}" : 'NOT NULL',
            $column['isPrimary'] ? 'AUTO_INCREMENT PRIMARY KEY' : ''
        ];

        return implode(' ', array_filter($parts));
    }
}