<?php

namespace Swidly\Core;

use Swidly\Core\DB;

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
        if (is_array($params)) {
            $params = array_map(function ($param) {
                return is_string($param) ? "'$param'" : $param;
            }, $params);
        }
        if (is_string($params)) {
            $params = [$params];
        }
        if (is_string($sql)) {
            $sql = str_replace('?', '%s', $sql);
        }
        $this->plannedSql[] = DB::query($sql, $params);
    }

    public function addColumn(string $table, string $column, string $type, bool $nullable = false, $default = null): void
    {
        $nullStr = $nullable ? 'NULL' : 'NOT NULL';
        $defaultStr = $default !== null ? "DEFAULT '$default'" : '';
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $type $nullStr $defaultStr";
        $this->addSql($sql);
    }

    public function getSql(): array
    {
        return $this->plannedSql;
    }

    public function run() {
        // Execute planned SQL
    }
}