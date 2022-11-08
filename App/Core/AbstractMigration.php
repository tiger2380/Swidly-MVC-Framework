<?php

namespace App;

abstract class AbstractMigration
{
    protected array $plannedSql = [];

    abstract public function up(): void;

    abstract public function down(): void;

    public function getDescription(): string
    {
        return '';
    }

    public function addSql($sql, $params = []): void
    {
        $this->plannedSql[] = DB::Query($sql, $params);
    }

    public function getSql(): array
    {
        return $this->plannedSql;
    }
}