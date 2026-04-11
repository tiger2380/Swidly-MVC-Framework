<?php

declare(strict_types=1);

namespace Swidly\Core\Attributes;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
class ForeignKey
{
    /**
     * Define a foreign key constraint
     * 
     * @param string|array $columns Local column(s)
     * @param string $referencedTable Referenced table name
     * @param string|array $referencedColumns Referenced column(s)
     * @param string|null $name Constraint name (auto-generated if null)
     * @param string $onDelete Action on delete (CASCADE, SET NULL, RESTRICT, NO ACTION)
     * @param string $onUpdate Action on update (CASCADE, SET NULL, RESTRICT, NO ACTION)
     */
    public function __construct(
        public string|array $columns,
        public string $referencedTable,
        public string|array $referencedColumns = 'id',
        public ?string $name = null,
        public string $onDelete = 'RESTRICT',
        public string $onUpdate = 'CASCADE'
    ) {
        // Normalize columns to array
        if (is_string($this->columns)) {
            $this->columns = [$this->columns];
        }
        if (is_string($this->referencedColumns)) {
            $this->referencedColumns = [$this->referencedColumns];
        }
    }

    /**
     * Get the local columns
     * @return array
     */
    public function getColumns(): array
    {
        return is_array($this->columns) ? $this->columns : [$this->columns];
    }

    /**
     * Get the referenced table
     * @return string
     */
    public function getReferencedTable(): string
    {
        return $this->referencedTable;
    }

    /**
     * Get the referenced columns
     * @return array
     */
    public function getReferencedColumns(): array
    {
        return is_array($this->referencedColumns) ? $this->referencedColumns : [$this->referencedColumns];
    }

    /**
     * Get the constraint name
     * @param string $tableName Table name for auto-generation
     * @return string
     */
    public function getName(string $tableName = ''): string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        // Auto-generate constraint name
        $columns = implode('_', $this->getColumns());
        return "fk_{$tableName}_{$columns}";
    }

    /**
     * Get the ON DELETE action
     * @return string
     */
    public function getOnDelete(): string
    {
        return strtoupper($this->onDelete);
    }

    /**
     * Get the ON UPDATE action
     * @return string
     */
    public function getOnUpdate(): string
    {
        return strtoupper($this->onUpdate);
    }
}
