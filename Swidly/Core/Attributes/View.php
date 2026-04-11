<?php

declare(strict_types=1);

namespace Swidly\Core\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class View
{
    /**
     * Define a database view
     * 
     * @param string $name View name
     * @param string $query SQL query for the view
     * @param bool $materialized Whether this is a materialized view (PostgreSQL)
     */
    public function __construct(
        public string $name,
        public string $query,
        public bool $materialized = false
    ) {}

    /**
     * Get the view name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the view query
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Check if this is a materialized view
     * @return bool
     */
    public function isMaterialized(): bool
    {
        return $this->materialized;
    }
}
