<?php

declare(strict_types=1);

namespace Swidly\Core\Database;

use Swidly\Core\DB;

/**
 * Schema builder for database migrations
 */
class Schema
{
    /**
     * Create a new table
     * 
     * @param string $tableName Table name
     * @param callable $callback Callback function to define table structure
     * @return string SQL statement
     */
    public static function create(string $tableName, callable $callback): string
    {
        $table = new TableBlueprint($tableName);
        $callback($table);
        return $table->toSql();
    }

    /**
     * Alter an existing table
     * 
     * @param string $tableName Table name
     * @param callable $callback Callback function to define alterations
     * @return string SQL statement
     */
    public static function alter(string $tableName, callable $callback): string
    {
        $table = new TableBlueprint($tableName, true);
        $callback($table);
        return $table->toSql();
    }

    /**
     * Drop a table
     * 
     * @param string $tableName Table name
     * @param bool $ifExists Add IF EXISTS clause
     * @return string SQL statement
     */
    public static function drop(string $tableName, bool $ifExists = true): string
    {
        $ifExistsClause = $ifExists ? 'IF EXISTS ' : '';
        return "DROP TABLE {$ifExistsClause}`{$tableName}`";
    }

    /**
     * Create a database view
     * 
     * @param string $viewName View name
     * @param string $query SQL query for the view
     * @param bool $orReplace Add OR REPLACE clause
     * @return string SQL statement
     */
    public static function createView(string $viewName, string $query, bool $orReplace = true): string
    {
        $orReplaceClause = $orReplace ? 'OR REPLACE ' : '';
        return "CREATE {$orReplaceClause}VIEW `{$viewName}` AS {$query}";
    }

    /**
     * Drop a view
     * 
     * @param string $viewName View name
     * @param bool $ifExists Add IF EXISTS clause
     * @return string SQL statement
     */
    public static function dropView(string $viewName, bool $ifExists = true): string
    {
        $ifExistsClause = $ifExists ? 'IF EXISTS ' : '';
        return "DROP VIEW {$ifExistsClause}`{$viewName}`";
    }

    /**
     * Create an index
     * 
     * @param string $tableName Table name
     * @param string|array $columns Column(s) to index
     * @param string|null $indexName Index name (auto-generated if null)
     * @param bool $unique Whether this is a unique index
     * @param string $type Index type (BTREE, HASH, FULLTEXT)
     * @return string SQL statement
     */
    public static function createIndex(
        string $tableName,
        string|array $columns,
        ?string $indexName = null,
        bool $unique = false,
        string $type = 'BTREE'
    ): string {
        $columns = is_array($columns) ? $columns : [$columns];
        $indexName = $indexName ?? 'idx_' . $tableName . '_' . implode('_', $columns);
        $uniqueClause = $unique ? 'UNIQUE ' : '';
        $columnsList = '`' . implode('`, `', $columns) . '`';
        $typeClause = strtoupper($type) !== 'BTREE' ? " USING {$type}" : '';
        
        return "CREATE {$uniqueClause}INDEX `{$indexName}` ON `{$tableName}` ({$columnsList}){$typeClause}";
    }

    /**
     * Drop an index
     * 
     * @param string $tableName Table name
     * @param string $indexName Index name
     * @return string SQL statement
     */
    public static function dropIndex(string $tableName, string $indexName): string
    {
        return "DROP INDEX `{$indexName}` ON `{$tableName}`";
    }

    /**
     * Add a foreign key constraint
     * 
     * @param string $tableName Table name
     * @param string|array $columns Local column(s)
     * @param string $referencedTable Referenced table
     * @param string|array $referencedColumns Referenced column(s)
     * @param string|null $constraintName Constraint name
     * @param string $onDelete ON DELETE action
     * @param string $onUpdate ON UPDATE action
     * @return string SQL statement
     */
    public static function addForeignKey(
        string $tableName,
        string|array $columns,
        string $referencedTable,
        string|array $referencedColumns = 'id',
        ?string $constraintName = null,
        string $onDelete = 'RESTRICT',
        string $onUpdate = 'CASCADE'
    ): string {
        $columns = is_array($columns) ? $columns : [$columns];
        $referencedColumns = is_array($referencedColumns) ? $referencedColumns : [$referencedColumns];
        $constraintName = $constraintName ?? 'fk_' . $tableName . '_' . implode('_', $columns);
        
        $columnsList = '`' . implode('`, `', $columns) . '`';
        $refColumnsList = '`' . implode('`, `', $referencedColumns) . '`';
        
        return "ALTER TABLE `{$tableName}` 
                ADD CONSTRAINT `{$constraintName}` 
                FOREIGN KEY ({$columnsList}) 
                REFERENCES `{$referencedTable}` ({$refColumnsList}) 
                ON DELETE {$onDelete} 
                ON UPDATE {$onUpdate}";
    }

    /**
     * Drop a foreign key constraint
     * 
     * @param string $tableName Table name
     * @param string $constraintName Constraint name
     * @return string SQL statement
     */
    public static function dropForeignKey(string $tableName, string $constraintName): string
    {
        return "ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`";
    }

    /**
     * Check if a table exists
     * 
     * @param string $tableName Table name
     * @return bool
     */
    public static function hasTable(string $tableName): bool
    {
        $db = DB::getInstance();
        $result = $db->query("SHOW TABLES LIKE ?", [$tableName]);
        return !empty($result);
    }

    /**
     * Check if a column exists in a table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @return bool
     */
    public static function hasColumn(string $tableName, string $columnName): bool
    {
        $db = DB::getInstance();
        $result = $db->query("SHOW COLUMNS FROM `{$tableName}` LIKE ?", [$columnName]);
        return !empty($result);
    }
}
