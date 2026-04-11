<?php

declare(strict_types=1);

namespace Swidly\Core\Database;

/**
 * Table blueprint for schema building
 */
class TableBlueprint
{
    protected string $tableName;
    protected bool $isAltering;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $foreignKeys = [];
    protected ?string $primaryKey = null;
    protected string $engine = 'InnoDB';
    protected string $charset = 'utf8mb4';
    protected string $collation = 'utf8mb4_unicode_ci';

    public function __construct(string $tableName, bool $isAltering = false)
    {
        $this->tableName = $tableName;
        $this->isAltering = $isAltering;
    }

    /**
     * Add an ID column (auto-increment primary key)
     */
    public function id(string $name = 'id'): self
    {
        $this->columns[$name] = [
            'type' => 'BIGINT',
            'unsigned' => true,
            'nullable' => false,
            'autoIncrement' => true,
            'primary' => true,
        ];
        $this->primaryKey = $name;
        return $this;
    }

    /**
     * Add a string column
     */
    public function string(string $name, int $length = 255): self
    {
        $this->columns[$name] = [
            'type' => 'VARCHAR',
            'length' => $length,
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Add a varchar column (alias for string)
     */
    public function varchar(string $name, int $length = 255): self
    {
        return $this->string($name, $length);
    }

    /**
     * Add a char column
     */
    public function char(string $name, int $length = 36): self
    {
        $this->columns[$name] = [
            'type' => 'CHAR',
            'length' => $length,
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Add an int column (alias for integer)
     */
    public function int(string $name, bool $unsigned = false): self
    {
        return $this->integer($name, $unsigned);
    }

    /**
     * Add a text column
     */
    public function text(string $name): self
    {
        $this->columns[$name] = [
            'type' => 'TEXT',
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Add an integer column
     */
    public function integer(string $name, bool $unsigned = false): self
    {
        $this->columns[$name] = [
            'type' => 'INT',
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Add a big integer column
     */
    public function bigInteger(string $name, bool $unsigned = false): self
    {
        $this->columns[$name] = [
            'type' => 'BIGINT',
            'unsigned' => $unsigned,
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Add a decimal column
     */
    public function decimal(string $name, int $precision = 10, int $scale = 2): self
    {
        $this->columns[$name] = [
            'type' => 'DECIMAL',
            'precision' => $precision,
            'scale' => $scale,
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Add a boolean column
     */
    public function boolean(string $name): self
    {
        $this->columns[$name] = [
            'type' => 'TINYINT',
            'length' => 1,
            'nullable' => false,
            'default' => 0,
        ];
        return $this;
    }

    /**
     * Add a date column
     */
    public function date(string $name): self
    {
        $this->columns[$name] = [
            'type' => 'DATE',
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Add a datetime column
     */
    public function datetime(string $name): self
    {
        $this->columns[$name] = [
            'type' => 'DATETIME',
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Add a timestamp column
     */
    public function timestamp(string $name): self
    {
        $this->columns[$name] = [
            'type' => 'TIMESTAMP',
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Add created_at and updated_at timestamp columns
     */
    public function timestamps(): self
    {
        $this->columns['created_at'] = [
            'type' => 'TIMESTAMP',
            'nullable' => false,
            'default' => 'CURRENT_TIMESTAMP',
        ];
        $this->columns['updated_at'] = [
            'type' => 'TIMESTAMP',
            'nullable' => false,
            'default' => 'CURRENT_TIMESTAMP',
            'onUpdate' => 'CURRENT_TIMESTAMP',
        ];
        return $this;
    }

    /**
     * Add an enum column
     */
    public function enum(string $name, array $values): self
    {
        $this->columns[$name] = [
            'type' => 'ENUM',
            'values' => $values,
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Add a JSON column
     */
    public function json(string $name): self
    {
        $this->columns[$name] = [
            'type' => 'JSON',
            'nullable' => false,
        ];
        return $this;
    }

    /**
     * Make the last column nullable
     */
    public function nullable(): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn]['nullable'] = true;
        }
        return $this;
    }

    /**
     * Set default value for the last column
     */
    public function default($value): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn]['default'] = $value;
        }
        return $this;
    }

    /**
     * Make the last column unique
     */
    public function unique(): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->indexes[] = [
                'type' => 'UNIQUE',
                'columns' => [$lastColumn],
                'name' => 'uniq_' . $this->tableName . '_' . $lastColumn,
            ];
        }
        return $this;
    }

    /**
     * Add an index to the last column
     */
    public function index(?string $name = null): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->indexes[] = [
                'type' => 'INDEX',
                'columns' => [$lastColumn],
                'name' => $name ?? 'idx_' . $this->tableName . '_' . $lastColumn,
            ];
        }
        return $this;
    }

    /**
     * Add a fulltext index to the last column
     */
    public function fulltext(?string $name = null): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->indexes[] = [
                'type' => 'FULLTEXT',
                'columns' => [$lastColumn],
                'name' => $name ?? 'ft_' . $this->tableName . '_' . $lastColumn,
            ];
        }
        return $this;
    }

    /**
     * Add a comment to the last column
     */
    public function comment(string $comment): self
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn) {
            $this->columns[$lastColumn]['comment'] = $comment;
        }
        return $this;
    }

    /**
     * Add a foreign key constraint
     */
    public function foreign(string $column): ForeignKeyBuilder
    {
        return new ForeignKeyBuilder($this, $column);
    }

    /**
     * Add a foreign key to the blueprint
     */
    public function addForeignKey(string $column, string $referencedTable, string $referencedColumn, string $onDelete, string $onUpdate): self
    {
        $this->foreignKeys[] = [
            'column' => $column,
            'referencedTable' => $referencedTable,
            'referencedColumn' => $referencedColumn,
            'onDelete' => $onDelete,
            'onUpdate' => $onUpdate,
            'name' => 'fk_' . $this->tableName . '_' . $column,
        ];
        return $this;
    }

    /**
     * Set table engine
     */
    public function engine(string $engine): self
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * Set table charset
     */
    public function charset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Set table collation
     */
    public function collation(string $collation): self
    {
        $this->collation = $collation;
        return $this;
    }

    /**
     * Generate SQL statement
     */
    public function toSql(): string
    {
        if ($this->isAltering) {
            return $this->buildAlterStatement();
        }
        return $this->buildCreateStatement();
    }

    /**
     * Build CREATE TABLE statement
     */
    protected function buildCreateStatement(): string
    {
        $sql = "CREATE TABLE `{$this->tableName}` (\n";
        
        $columnDefinitions = [];
        foreach ($this->columns as $name => $column) {
            $columnDefinitions[] = $this->buildColumnDefinition($name, $column);
        }
        
        $sql .= "  " . implode(",\n  ", $columnDefinitions);
        
        // Add indexes
        foreach ($this->indexes as $index) {
            $sql .= ",\n  " . $this->buildIndexDefinition($index);
        }
        
        // Add foreign keys
        foreach ($this->foreignKeys as $fk) {
            $sql .= ",\n  " . $this->buildForeignKeyDefinition($fk);
        }
        
        $sql .= "\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation}";
        
        return $sql;
    }

    /**
     * Build ALTER TABLE statement
     */
    protected function buildAlterStatement(): string
    {
        $alterations = [];
        
        foreach ($this->columns as $name => $column) {
            $alterations[] = "ADD COLUMN " . $this->buildColumnDefinition($name, $column);
        }
        
        foreach ($this->indexes as $index) {
            $alterations[] = "ADD " . $this->buildIndexDefinition($index);
        }
        
        foreach ($this->foreignKeys as $fk) {
            $alterations[] = "ADD " . $this->buildForeignKeyDefinition($fk);
        }
        
        return "ALTER TABLE `{$this->tableName}` " . implode(", ", $alterations);
    }

    /**
     * Build column definition
     */
    protected function buildColumnDefinition(string $name, array $column): string
    {
        $definition = "`{$name}` {$column['type']}";
        
        if (isset($column['length'])) {
            $definition .= "({$column['length']})";
        } elseif (isset($column['precision']) && isset($column['scale'])) {
            $definition .= "({$column['precision']},{$column['scale']})";
        } elseif (isset($column['values'])) {
            $values = array_map(fn($v) => "'{$v}'", $column['values']);
            $definition .= "(" . implode(',', $values) . ")";
        }
        
        if (!empty($column['unsigned'])) {
            $definition .= " UNSIGNED";
        }
        
        if (empty($column['nullable'])) {
            $definition .= " NOT NULL";
        } else {
            $definition .= " NULL";
        }
        
        if (isset($column['default'])) {
            if ($column['default'] === 'CURRENT_TIMESTAMP') {
                $definition .= " DEFAULT CURRENT_TIMESTAMP";
            } else {
                $definition .= " DEFAULT '{$column['default']}'";
            }
        }
        
        if (!empty($column['autoIncrement'])) {
            $definition .= " AUTO_INCREMENT";
        }
        
        if (isset($column['onUpdate'])) {
            $definition .= " ON UPDATE {$column['onUpdate']}";
        }
        
        if (!empty($column['primary'])) {
            $definition .= " PRIMARY KEY";
        }
        
        if (isset($column['comment'])) {
            $definition .= " COMMENT '{$column['comment']}'";
        }
        
        return $definition;
    }

    /**
     * Build index definition
     */
    protected function buildIndexDefinition(array $index): string
    {
        $columns = '`' . implode('`, `', $index['columns']) . '`';
        
        if ($index['type'] === 'UNIQUE') {
            return "UNIQUE KEY `{$index['name']}` ({$columns})";
        } elseif ($index['type'] === 'FULLTEXT') {
            return "FULLTEXT KEY `{$index['name']}` ({$columns})";
        } else {
            return "KEY `{$index['name']}` ({$columns})";
        }
    }

    /**
     * Build foreign key definition
     */
    protected function buildForeignKeyDefinition(array $fk): string
    {
        return "CONSTRAINT `{$fk['name']}` FOREIGN KEY (`{$fk['column']}`) " .
               "REFERENCES `{$fk['referencedTable']}` (`{$fk['referencedColumn']}`) " .
               "ON DELETE {$fk['onDelete']} ON UPDATE {$fk['onUpdate']}";
    }
}

/**
 * Foreign key builder helper
 */
class ForeignKeyBuilder
{
    protected TableBlueprint $blueprint;
    protected string $column;
    protected string $referencedTable = '';
    protected string $referencedColumn = 'id';
    protected string $onDelete = 'RESTRICT';
    protected string $onUpdate = 'CASCADE';

    public function __construct(TableBlueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    public function references(string $column): self
    {
        $this->referencedColumn = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->referencedTable = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        $this->onDelete = 'CASCADE';
        return $this;
    }

    public function nullOnDelete(): self
    {
        $this->onDelete = 'SET NULL';
        return $this;
    }

    public function setNullOnDelete(): self
    {
        return $this->nullOnDelete();
    }

    public function __destruct()
    {
        $this->blueprint->addForeignKey(
            $this->column,
            $this->referencedTable,
            $this->referencedColumn,
            $this->onDelete,
            $this->onUpdate
        );
    }
}
