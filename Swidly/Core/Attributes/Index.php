<?php

declare(strict_types=1);

namespace Swidly\Core\Attributes;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
class Index
{
    /**
     * Define a database index
     * 
     * @param string|array $columns Column name(s) for the index
     * @param string|null $name Index name (auto-generated if null)
     * @param bool $unique Whether this is a unique index
     * @param string $type Index type (BTREE, HASH, FULLTEXT, SPATIAL)
     */
    public function __construct(
        public string|array $columns,
        public ?string $name = null,
        public bool $unique = false,
        public string $type = 'BTREE'
    ) {
        // Normalize columns to array
        if (is_string($this->columns)) {
            $this->columns = [$this->columns];
        }
    }

    /**
     * Get the columns for this index
     * @return array
     */
    public function getColumns(): array
    {
        return is_array($this->columns) ? $this->columns : [$this->columns];
    }

    /**
     * Get the index name
     * @param string $tableName Table name for auto-generation
     * @return string
     */
    public function getName(string $tableName = ''): string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        // Auto-generate index name
        $prefix = $this->unique ? 'uniq' : 'idx';
        $columns = implode('_', $this->getColumns());
        return "{$prefix}_{$tableName}_{$columns}";
    }

    /**
     * Check if this is a unique index
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * Get the index type
     * @return string
     */
    public function getType(): string
    {
        return strtoupper($this->type);
    }
}
