<?php

namespace Swidly\Core\Commands;

use Swidly\Core\Attributes\Column;
use Swidly\Core\Attributes\Table;
use Swidly\Core\DB;
use Swidly\Core\Database\SqlBuilder;

class MigrationCommand extends AbstractCommand 
{
    private const MIGRATION_TEMPLATE = <<<'STR'
<?php

    declare(strict_types=1);

    namespace Swidly\Migrations;

    use Swidly\Core\AbstractMigration;

    final class Version%s extends AbstractMigration
    {
        public function getDescription(): string
        {
            return '';
        }

        public function up(): void
        {
            {up}
        }

        public function down(): void
        {
            {down}
        }
    }
STR;

    public function execute(): void 
    {
        $name = $this->options['name'] ?? '';
        $theme = $this->options['theme'] ?? [];
        $options = $this->options['options'] ?? [];
        $args = $this->options['args'] ?? [];
        $filename = $args[1] ?? '';

        switch($name) {
            case 'create':
                $this->createMigrationFromModels($theme, $filename);
                break;
            case 'migrate':
                $this->migrate($theme, $options, $filename);
                break;
            case 'rollback':
                $this->rollback($theme, $options);
                break;
            case 'execute':
                $this->executeMigration($theme, $options, $filename);
                break;
            case 'status':
                $changes = $this->checkSchemaUpdates();
                $this->displayMigrationStatus($changes);
                break;
            case 'check':
                $changes = $this->checkSchemaUpdates();
                if (empty($changes)) {
                    formatPrintLn(['green'], "Database schema is up to date");
                } else {
                    formatPrintLn(['yellow'], "Database updates needed:");
                    foreach ($changes as $table => $diff) {
                        formatPrintLn(['cyan'], "\nTable: $table");
                        if (!empty($diff['new'])) {
                            formatPrintLn(['green'], "New columns: " . implode(', ', array_keys($diff['new'])));
                        }
                        if (!empty($diff['modified'])) {
                            formatPrintLn(['yellow'], "Modified columns: " . implode(', ', array_keys($diff['modified'])));
                        }
                        if (!empty($diff['removed'])) {
                            formatPrintLn(['red'], "Removed columns: " . implode(', ', $diff['removed']));
                        }
                    }
                }
                break;
            default:
                throw new \InvalidArgumentException("Unknown migration command: $name");
        }

        if (isset($options['u']) && $options['u']) {
            $migrations = SWIDLY_ROOT . '/Migrations';
            print_r($migrations);
        }

        if (isset($this->options['verbose']) && $this->options['verbose']) {
            
        }
    }

    public function createMigrationFromModels($theme, $filename = null): void
    {
        try {
            formatPrintLn(['cyan', 'bold'], "Creating migration from models...");
            
            $entities = $this->getEntities($filename);
            if (empty($entities)) {
                throw new \RuntimeException("No entities found to migrate.");
            }
    
            $addUpSqls = [];
            $addDownSqls = [];
    
            foreach ($entities as $entity) {
                $this->processMigrationForEntity($entity, $addUpSqls, $addDownSqls);
            }
    
            $migrationFile = $this->createMigration($addUpSqls, $addDownSqls);

            if ($migrationFile) {
                formatPrintLn(['green'], "✓ Migration file created successfully: " . basename($migrationFile));
            } else {
                formatPrintLn(['red'], "X Failed to create Migration file successfully");
            }
            
        } catch (\Exception $e) {
            formatPrintLn(['red'], "✗ Migration creation failed: " . $e->getMessage());
            if (isset($this->options['verbose']) && $this->options['verbose']) {
                formatPrintLn(['red'], $e->getTraceAsString());
            }
        }
    }

    public function migrate($theme, $options, $filename = null): void
    {
        try {
            formatPrintLn(['cyan', 'bold'], "Starting migration process...");
            
            // Check for pending changes first
            $changes = $this->checkSchemaUpdates();
            if (empty($changes)) {
                formatPrintLn(['green'], "No changes needed. Database is up to date.");
                return;
            }

            // Check which tables need alterations
            foreach ($changes as $tableName => $diff) {
                if ($diff['needs_alter']) {
                    formatPrintLn(['yellow'], "Table '$tableName' needs schema updates:");
                    if (!empty($diff['new'])) {
                        formatPrintLn(['green'], "  + Adding columns: " . implode(', ', array_keys($diff['new'])));
                    }
                    if (!empty($diff['modified'])) {
                        formatPrintLn(['yellow'], "  ~ Modifying columns: " . implode(', ', array_keys($diff['modified'])));
                    }
                    if (!empty($diff['removed'])) {
                        formatPrintLn(['red'], "  - Removing columns: " . implode(', ', $diff['removed']));
                    }
                }
            }
    
            $entities = $this->getEntities($filename);
            if (empty($entities)) {
                throw new \RuntimeException("No entities found to migrate.");
            }
    
            $addUpSqls = [];
            $addDownSqls = [];
    
            foreach ($entities as $entity) {
                $this->processMigrationForEntity($entity, $addUpSqls, $addDownSqls);
            }
    
            $migrationFile = $this->createMigration($addUpSqls, $addDownSqls);

            if ($migrationFile) {
                formatPrintLn(['green'], "✓ Migration file created successfully: " . basename($migrationFile));
            } else {
                formatPrintLn(['red'], "X Failed to create Migration file successfully");
            }
            
        } catch (\Exception $e) {
            formatPrintLn(['red'], "✗ Migration failed: " . $e->getMessage());
            if (isset($options['verbose']) && $options['verbose']) {
                formatPrintLn(['red'], $e->getTraceAsString());
            }
        }
    }

    public function rollback($theme, $options): void
    {
        try {
            formatPrintLn(['cyan', 'bold'], "Starting rollback process...");
            
            // Get the latest migration file
            $migrationsDir = SWIDLY_ROOT . 'Migrations/';
            $files = glob($migrationsDir . 'Version*.php');
            
            if (empty($files)) {
                formatPrintLn(['yellow'], "No migrations found to rollback.");
                return;
            }
            
            // Sort files by name (which includes timestamp) in descending order
            rsort($files);
            $latestMigration = basename($files[0], '.php');
            
            formatPrintLn(['yellow'], "Rolling back migration: $latestMigration");
            
            // Execute the rollback
            $this->executeMigration($theme, ['down' => true], $latestMigration);
            
            // Optionally remove the migration file
            if (isset($options['delete-file']) && $options['delete-file']) {
                unlink($files[0]);
                formatPrintLn(['green'], "✓ Migration file removed: $latestMigration");
            }
            
            formatPrintLn(['green'], "✓ Rollback completed successfully");
            
        } catch (\Exception $e) {
            formatPrintLn(['red'], "✗ Rollback failed: " . $e->getMessage());
            if (isset($options['verbose']) && $options['verbose']) {
                formatPrintLn(['red'], $e->getTraceAsString());
            }
        }
    }

    public function createMigration($upSql = [], $downSql = []): string
    {
        $result = $this->makeMigrationFile($upSql, $downSql);
        $migrationFile = $this->makeMigrationFileName();

        file_put_contents($migrationFile, $result);

        return $migrationFile;
    }

    public function executeMigration($theme, $options, $version): void
    {
        // Implementation for executing a specific migration
        $migrationFile = SWIDLY_ROOT . 'Migrations/' . $version . '.php';
        if (!file_exists($migrationFile)) {
            throw new \RuntimeException("Migration file not found: $migrationFile");
        }
        include_once $migrationFile;
        $migrationClass = 'Swidly\\Migrations\\' . $version;
        if (!class_exists($migrationClass)) {
            throw new \RuntimeException("Migration class not found: $migrationClass");
        }

        $migration = new $migrationClass();

        if (isset($options['u'])) {
            if (!method_exists($migration, 'up')) {
                throw new \RuntimeException("Migration class does not have an 'up' method: $migrationClass");
            }

            $migration->up();
        } else {
            if (!method_exists($migration, 'down')) {
                throw new \RuntimeException("Migration class does not have a 'down' method: $migrationClass");
            }

            $migration->down();
        }
        // Optionally, you can log the migration execution
    }

    private function makeMigrationFileName($name = ''): string
    {
        $date = date('mdYhi');
        if (isset($name) && !empty($name)) {
            $date = $name;
        }
        return SWIDLY_ROOT . 'Migrations/Version' . $date . '.php';
    }

    private function makeMigrationFile($upSqls = [], $downSqls = []): string
    {
        $date = date('mdYhi');
        $migration = sprintf(self::MIGRATION_TEMPLATE, $date);
        $result = preg_replace('/({up})/', implode(' ', $upSqls), $migration);
        $result = preg_replace('/({down})/', implode(' ', $downSqls), $result);
        return $result;
    }

    private function getEntities(string $filename = null): array
    {
        $theme = $this->options['theme'] ?? [];
        $entities = [];
        if (isset($filename) && !empty($filename)) {
            $modelFilenames = [$filename];
        } else {
            $models = array_diff(scandir($theme['base'].'/models', ), array('.', '..'));
            $modelFilenames = array_map(fn($model) => pathinfo($model)['filename'], $models);
        }

        foreach ($modelFilenames as $model) {
            $className = "Swidly\\themes\\{$theme['name']}\\models\\$model";
         
            if(class_exists($className)) {
                $instance = new $className();

                array_push($entities, new \ReflectionClass(get_class($instance)));
            } else {
                echo 'Class does not exist:1 '.$className;
            }
        }
    
        return $entities;
    }

    private function displayMigrationStatus($changes): void
    {
        if (empty($changes)) {
            formatPrintLn(['green'], "✓ Database schema is up to date");
            return;
        }
    
        formatPrintLn(['yellow'], "! Database updates needed:");
        foreach ($changes as $table => $diff) {
            formatPrintLn(['cyan'], "\nTable: $table");
            foreach ($diff as $type => $columns) {
                $color = match($type) {
                    'new' => 'green',
                    'modified' => 'yellow',
                    'removed' => 'red',
                    default => 'white'
                };
                
                if (is_array($columns)) {
                    $columns = array_keys($columns);
                } else {
                    $columns = [$columns];
                }
                
                formatPrintLn([$color], ucfirst($type) . ": " . implode(', ', array_keys($columns)));
            }
        }
    }

    public function checkSchemaUpdates(): array
    {
        $changes = [];
        $entities = $this->getEntities();
        
        foreach ($entities as $entity) {
            $tableInstance = $entity->getAttributes(Table::class)[0]->newInstance();
            $tableName = $tableInstance->name;
            
            // Get current table schema from database
            $currentSchema = $this->getCurrentTableSchema($tableName);
            
            // Get model defined schema
            $modelSchema = $this->getModelSchema($entity);
            
            // Compare schemas
            $tableDiff = $this->compareSchemas($currentSchema, $modelSchema);
            
            if (!empty($tableDiff)) {
                $changes[$tableName] = $tableDiff;
            }
        }
        
        return $changes;
    }

    private function getCurrentTableSchema(string $tableName): array
    {
        $schema = [];
        $sql = "SHOW COLUMNS FROM {$tableName}";
        
        try {
            $columns = DB::query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                $schema[$column['Field']] = [
                    'type' => $column['Type'],
                    'null' => $column['Null'] === 'YES',
                    'key' => $column['Key'],
                    'default' => $column['Default'],
                    'extra' => $column['Extra']
                ];
            }
        } catch (\PDOException $e) {
            // Table doesn't exist
            return [];
        }
        
        return $schema;
    }

    private function getModelSchema(\ReflectionClass $entity): array
    {
        $schema = [];
        $props = $entity->getProperties();
        
        foreach ($props as $prop) {
            $attributes = $prop->getAttributes(Column::class);
            foreach ($attributes as $attribute) {
                $name = $prop->getName();
                $instance = $attribute->newInstance();
                
                $schema[$name] = [
                    'type' => $this->getColumnDefinition($instance),
                    'null' => $instance->nullable ?? false,
                    'key' => $instance->isPrimary ? 'PRI' : '',
                    'default' => $instance->default ?? null,
                    'extra' => $instance->isPrimary ? 'auto_increment' : ''
                ];
            }
        }
        
        return $schema;
    }

    private function getColumnDefinition($columnAttr): string
    {
        $type = $columnAttr->type->value;
        $length = $columnAttr->length ?? ($type === 'varchar' ? 255 : null);
        
        return $length ? "{$type}({$length})" : $type;
    }

    private function compareSchemas(array $current, array $model): array
    {
        $diff = [
            'new' => [],          // Columns to ADD
            'modified' => [],     // Columns to ALTER
            'removed' => [],      // Columns to DROP
            'needs_alter' => false // Flag to indicate if ALTER TABLE is needed
        ];
        
        // Check for removed columns
        $diff['removed'] = array_keys(array_diff_key($current, $model));
        
        // Check for new or modified columns
        foreach ($model as $column => $definition) {
            if (!isset($current[$column])) {
                $diff['new'][$column] = $definition;
                $diff['needs_alter'] = true;
            } elseif ($this->isColumnModified($current[$column], $definition)) {
                $diff['modified'][$column] = [
                    'from' => $current[$column],
                    'to' => $definition
                ];
                $diff['needs_alter'] = true;
            }
        }
        
        // If any columns were removed, we need to alter
        if (!empty($diff['removed'])) {
            $diff['needs_alter'] = true;
        }
        
        return array_filter($diff, function($value) {
            return !empty($value) || is_bool($value);
        });
    }

    private function isColumnModified(array $current, array $new): bool
    {
        return $current['type'] !== $new['type'] 
            || $current['null'] !== $new['null']
            || $current['key'] !== $new['key']
            || $current['default'] !== $new['default']
            || $current['extra'] !== $new['extra'];
    }

    private function processMigrationForEntity(\ReflectionClass $entity, array &$addUpSqls, array &$addDownSqls): void 
    {
        $tableInstance = $entity->getAttributes(Table::class)[0]->newInstance();
        $tableName = $tableInstance->name;
        
        $sqlBuilder = new SqlBuilder($tableName);
        $props = $entity->getProperties();

        foreach ($props as $prop) {
            if ($attributes = $prop->getAttributes(Column::class)) {
                $sqlBuilder->addColumn($prop, $attributes[0]->newInstance());
            }
        }

        $addUpSqls[] = $sqlBuilder->getCreateTableSql();
        $addDownSqls[] = $sqlBuilder->getDropTableSql();
    }
}