<?php

namespace Swidly\Core\Database;

class SqlBuilder 
{
    private string $tableName;
    private array $columns = [];
    
    // Type length mappings for different database types
    private const TYPE_DEFAULTS = [
        'varchar' => 255,
        'char' => 100,
        'int' => null,
        'integer' => null,
        'bigint' => null,
        'smallint' => null,
        'tinyint' => null,
        'decimal' => '10,2',
        'float' => null,
        'double' => null,
        'text' => null,
        'longtext' => null,
        'mediumtext' => null,
        'datetime' => null,
        'timestamp' => null,
        'date' => null,
        'time' => null,
        'boolean' => null,
        'bool' => null,
        'json' => null,
    ];

    // Types that don't accept length specifications
    private const NO_LENGTH_TYPES = [
        'int', 'integer', 'bigint', 'smallint', 'tinyint',
        'text', 'longtext', 'mediumtext', 'datetime', 'timestamp',
        'date', 'time', 'boolean', 'bool', 'json', 'float', 'double'
    ];

    public function __construct(string $tableName) 
    {
        $this->tableName = $this->sanitizeIdentifier($tableName);
    }

    public function addColumn(\ReflectionProperty $property, $columnAttr): void 
    {
        $name = $property->getName();
        $type = strtolower($columnAttr->type->value);
        $length = $columnAttr->length ?? self::TYPE_DEFAULTS[$type] ?? null;
        $nullable = $columnAttr->nullable ?? false;
        $isPrimary = $columnAttr->isPrimary ?? false;
        $default = $columnAttr->default ?? null;

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
        if (empty($this->columns)) {
            throw new \RuntimeException("Cannot create table '{$this->tableName}' with no columns");
        }

        $columnDefinitions = [];
        foreach ($this->columns as $name => $column) {
            $columnDefinitions[] = $this->buildColumnDefinition($name, $column);
        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (\n    " 
             . implode(",\n    ", $columnDefinitions) 
             . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return '$this->addSql(\'' . addslashes($sql) . "');\n";
    }

    public function getDropTableSql(): string 
    {
        return '$this->addSql(\'DROP TABLE IF EXISTS `' . $this->tableName . "`');\n";
    }

    public function getAddColumnSql(string $columnName, array $columnDef): string
    {
        $columnDef = $this->buildColumnDefinition($columnName, $columnDef);
        // Use IF NOT EXISTS to prevent errors if column already exists
        return '$this->addSql(\'ALTER TABLE `' . $this->tableName . '` ADD COLUMN IF NOT EXISTS ' . $columnDef . "');\n";
    }

    public function getModifyColumnSql(string $columnName, array $columnDef): string
    {
        $columnDef = $this->buildColumnDefinition($columnName, $columnDef);
        return '$this->addSql(\'ALTER TABLE `' . $this->tableName . '` MODIFY COLUMN ' . $columnDef . "');\n";
    }

    public function getDropColumnSql(string $columnName): string
    {
        $safeColumnName = $this->sanitizeIdentifier($columnName);
        // Use IF EXISTS syntax to prevent errors if column doesn't exist
        return '$this->addSql(\'ALTER TABLE `' . $this->tableName . '` DROP COLUMN IF EXISTS `' . $safeColumnName . "`');\n";
    }

    public function getAddIndexSql(string $indexName, array $columns, bool $unique = false): string
    {
        $type = $unique ? 'UNIQUE INDEX' : 'INDEX';
        $cols = implode('`, `', array_map([$this, 'sanitizeIdentifier'], $columns));
        // Use IF NOT EXISTS to prevent errors if index already exists
        return '$this->addSql(\'ALTER TABLE `' . $this->tableName . '` ADD ' . $type . ' IF NOT EXISTS `' . $indexName . '` (`' . $cols . "`)');\n";
    }

    public function getDropIndexSql(string $indexName): string
    {
        // Use IF EXISTS to prevent errors if index doesn't exist
        return '$this->addSql(\'ALTER TABLE `' . $this->tableName . '` DROP INDEX IF EXISTS `' . $indexName . "`');\n";
    }

    public function getRenameColumnSql(string $oldName, string $newName, array $columnDef): string
    {
        $newDef = $this->buildColumnDefinition($newName, $columnDef);
        return '$this->addSql(\'ALTER TABLE `' . $this->tableName . '` CHANGE `' . $oldName . '` ' . $newDef . "');\n";
    }

    private function buildColumnDefinition(string $name, array $column): string 
    {
        $name = $this->sanitizeIdentifier($name);
        $type = strtolower($column['type'] ?? 'varchar');
        $length = $column['length'] ?? null;
        $nullable = $column['nullable'] ?? true;
        $isPrimary = $column['isPrimary'] ?? false;
        $default = $column['default'] ?? null;

        // Build type with length
        $typeDef = strtoupper($type);
        if ($length !== null && !in_array($type, self::NO_LENGTH_TYPES, true)) {
            $typeDef .= "({$length})";
        }

        $parts = ["`{$name}`", $typeDef];

        // Add NOT NULL or NULL
        if (!$nullable && !$isPrimary) {
            $parts[] = 'NOT NULL';
        } elseif ($nullable && !$isPrimary) {
            $parts[] = 'NULL';
        }

        // Add DEFAULT value
        if ($default !== null && !$isPrimary) {
            if (in_array(strtoupper($default), ['CURRENT_TIMESTAMP', 'NOW()'], true)) {
                $parts[] = "DEFAULT {$default}";
            } elseif ($default === 'NULL') {
                $parts[] = 'DEFAULT NULL';
            } else {
                $parts[] = "DEFAULT '" . addslashes($default) . "'";
            }
        } elseif ($nullable && !$isPrimary && $default === null) {
            $parts[] = 'DEFAULT NULL';
        }

        // Add AUTO_INCREMENT PRIMARY KEY
        if ($isPrimary) {
            if (in_array($type, ['int', 'integer', 'bigint', 'smallint'], true)) {
                $parts[] = 'NOT NULL AUTO_INCREMENT PRIMARY KEY';
            } else {
                $parts[] = 'NOT NULL PRIMARY KEY';
            }
        }

        return implode(' ', $parts);
    }

    private function sanitizeIdentifier(string $identifier): string
    {
        // Remove backticks and validate identifier
        $identifier = trim(str_replace('`', '', $identifier));
        
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException("Invalid identifier: {$identifier}");
        }
        
        return $identifier;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function hasColumn(string $columnName): bool
    {
        return isset($this->columns[$columnName]);
    }

    public function removeColumn(string $columnName): void
    {
        unset($this->columns[$columnName]);
    }
}