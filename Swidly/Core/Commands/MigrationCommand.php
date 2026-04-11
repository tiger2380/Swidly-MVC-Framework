<?php

namespace Swidly\Core\Commands;

use Swidly\Core\Attributes\Column;
use Swidly\Core\Attributes\Table;
use Swidly\Core\DB;
use Swidly\Core\Database\Schema;

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
        $filename = $args[0] ?? '';

        try {
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
                    if (empty($filename)) {
                        throw new \InvalidArgumentException("Migration version is required for execute command");
                    }
                    $this->executeMigration($theme, $options, $filename);
                    break;
                case 'status':
                    $changes = $this->checkSchemaUpdates();
                    $this->displayMigrationStatus($changes);
                    break;
                case 'check':
                    $changes = $this->checkSchemaUpdates();
                    if (empty($changes)) {
                        formatPrintLn(['green'], "✓ Database schema is up to date");
                    } else {
                        formatPrintLn(['yellow'], "! Database updates needed:");
                        foreach ($changes as $table => $diff) {
                            formatPrintLn(['cyan'], "\n  Table: $table");
                            if (!empty($diff['new'])) {
                                formatPrintLn(['green'], "    + New columns: " . implode(', ', array_keys($diff['new'])));
                            }
                            if (!empty($diff['modified'])) {
                                formatPrintLn(['yellow'], "    ~ Modified columns: " . implode(', ', array_keys($diff['modified'])));
                            }
                            if (!empty($diff['removed'])) {
                                formatPrintLn(['red'], "    - Removed columns: " . implode(', ', $diff['removed']));
                            }
                        }
                    }
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown migration command: $name. Valid commands: create, migrate, rollback, execute, status, check");
            }

            if (isset($options['u']) && $options['u']) {
                $migrations = SWIDLY_ROOT . '/Migrations';
            }

            if (isset($this->options['verbose']) && $this->options['verbose']) {
                formatPrintLn(['blue'], "Command executed: $name");
            }
        } catch (\InvalidArgumentException $e) {
            formatPrintLn(['red'], "✗ Invalid argument: " . $e->getMessage());
            exit(1);
        } catch (\RuntimeException $e) {
            formatPrintLn(['red'], "✗ Runtime error: " . $e->getMessage());
            if (isset($this->options['verbose']) && $this->options['verbose']) {
                formatPrintLn(['red'], $e->getTraceAsString());
            }
            exit(1);
        } catch (\Exception $e) {
            formatPrintLn(['red'], "✗ Unexpected error: " . $e->getMessage());
            if (isset($this->options['verbose']) && $this->options['verbose']) {
                formatPrintLn(['red'], $e->getTraceAsString());
            }
            exit(1);
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
        if (empty($upSql)) {
            throw new \InvalidArgumentException("Cannot create migration with no SQL statements");
        }

        $result = $this->makeMigrationFile($upSql, $downSql);
        $migrationFile = $this->makeMigrationFileName();

        // Ensure migrations directory exists
        $migrationDir = dirname($migrationFile);
        if (!is_dir($migrationDir)) {
            if (!mkdir($migrationDir, 0755, true)) {
                throw new \RuntimeException("Failed to create migrations directory: $migrationDir");
            }
        }

        // Check if file already exists
        if (file_exists($migrationFile)) {
            throw new \RuntimeException("Migration file already exists: $migrationFile");
        }

        $bytesWritten = file_put_contents($migrationFile, $result);
        if ($bytesWritten === false) {
            throw new \RuntimeException("Failed to write migration file: $migrationFile");
        }

        // Verify the file was written correctly
        if (!file_exists($migrationFile) || !is_readable($migrationFile)) {
            throw new \RuntimeException("Migration file created but is not readable: $migrationFile");
        }

        return $migrationFile;
    }

    public function executeMigration($theme, $options, $version): void
    {
        // Validate version format
        if (!preg_match('/^Version[0-9]{12}$/', $version)) {
            throw new \InvalidArgumentException("Invalid migration version format: $version (expected format: Version + 12 digits)");
        }

        $migrationFile = SWIDLY_ROOT . 'Migrations/' . $version . '.php';
        
        if (!file_exists($migrationFile)) {
            throw new \RuntimeException("Migration file not found: $migrationFile");
        }

        if (!is_readable($migrationFile)) {
            throw new \RuntimeException("Migration file is not readable: $migrationFile");
        }
        var_dump($migrationFile);
        try {
            include_once $migrationFile;
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to include migration file: " . $e->getMessage());
        }

        $migrationClass = 'Swidly\\Migrations\\' . $version;
        
        if (!class_exists($migrationClass)) {
            throw new \RuntimeException("Migration class not found: $migrationClass");
        }

        $migration = new $migrationClass();

        $direction = isset($options['u']) ? 'up' : 'down';
        
        if (!method_exists($migration, $direction)) {
            throw new \RuntimeException("Migration class does not have a '$direction' method: $migrationClass");
        }

        formatPrintLn(['cyan'], "Executing migration $direction: $version");
        
        try {
            $migration->$direction();
            formatPrintLn(['green'], "✓ Migration $direction executed successfully");
        } catch (\PDOException $e) {
            formatPrintLn(['red'], "✗ Database error during migration: " . $e->getMessage());
            throw new \RuntimeException("Migration $direction failed: " . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            formatPrintLn(['red'], "✗ Migration $direction failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function makeMigrationFileName($name = ''): string
    {
        $date = date('mdYhi');
        
        if (isset($name) && !empty($name)) {
            // Validate custom name format
            if (!preg_match('/^[0-9]{10}$/', $name)) {
                throw new \InvalidArgumentException("Invalid migration name format: $name (expected 10 digits)");
            }
            $date = $name;
        }

        if (!defined('SWIDLY_ROOT')) {
            throw new \RuntimeException("SWIDLY_ROOT constant is not defined");
        }
        
        return SWIDLY_ROOT . 'Migrations/Version' . $date . '.php';
    }

    private function makeMigrationFile($upSqls = [], $downSqls = []): string
    {
        if (!is_array($upSqls)) {
            throw new \InvalidArgumentException("upSqls must be an array");
        }
        
        if (!is_array($downSqls)) {
            throw new \InvalidArgumentException("downSqls must be an array");
        }

        $date = date('mdYhi');
        $migration = sprintf(self::MIGRATION_TEMPLATE, $date);
        
        // Format SQL statements with proper indentation
        $upSqlFormatted = empty($upSqls) ? '// No up SQL' : "\n            " . implode("\n            ", $upSqls);
        $downSqlFormatted = empty($downSqls) ? '// No down SQL' : "\n            " . implode("\n            ", $downSqls);
        
        $result = str_replace('{up}', $upSqlFormatted, $migration);
        $result = str_replace('{down}', $downSqlFormatted, $result);
        
        return $result;
    }

    private function getEntities(string $filename = null): array
    {
        $theme = $this->options['theme'] ?? [];
        
        if (empty($theme) || !isset($theme['base']) || !isset($theme['name'])) {
            throw new \RuntimeException("Theme configuration is missing or incomplete");
        }

        $modelsPath = $theme['base'] . '/models';
        
        if (!is_dir($modelsPath)) {
            throw new \RuntimeException("Models directory not found: $modelsPath");
        }

        if (!is_readable($modelsPath)) {
            throw new \RuntimeException("Models directory is not readable: $modelsPath");
        }

        $entities = [];
        
        if (isset($filename) && !empty($filename)) {
            // Remove .php extension if provided
            $filename = preg_replace('/\.php$/', '', $filename);
            $modelFilenames = [$filename];
        } else {
            $files = scandir($modelsPath);
            if ($files === false) {
                throw new \RuntimeException("Failed to read models directory: $modelsPath");
            }
            
            $models = array_diff($files, ['.', '..', '.gitkeep']);
            $modelFilenames = array_map(fn($model) => pathinfo($model)['filename'], $models);
        }

        foreach ($modelFilenames as $model) {
            $className = "Swidly\\themes\\{$theme['name']}\\models\\$model";
         
            if (!class_exists($className)) {
                formatPrintLn(['yellow'], "⚠ Skipping non-existent class: $className");
                continue;
            }

            try {
                $instance = new $className();
                $reflection = new \ReflectionClass(get_class($instance));
                
                // Verify class has Table attribute
                if (empty($reflection->getAttributes(Table::class))) {
                    formatPrintLn(['yellow'], "⚠ Skipping class without Table attribute: $className");
                    continue;
                }
                
                $entities[] = $reflection;
            } catch (\Exception $e) {
                formatPrintLn(['red'], "✗ Error processing class $className: " . $e->getMessage());
                continue;
            }
        }

        if (empty($entities)) {
            throw new \RuntimeException("No valid entity models found in $modelsPath");
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
        if (empty($tableName)) {
            throw new \InvalidArgumentException("Table name cannot be empty");
        }

        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            throw new \InvalidArgumentException("Invalid table name: $tableName");
        }

        // First, check if table exists
        try {
            $checkSql = "SHOW TABLES LIKE '{$tableName}'";
            $tableExists = DB::query($checkSql);
            if (!$tableExists || empty($tableExists->fetchAll())) {
                // Table doesn't exist, return empty schema
                return [];
            }
        } catch (\PDOException $e) {
            // If we can't check, assume table doesn't exist
            return [];
        }

        $schema = [];
        $sql = "SHOW COLUMNS FROM `{$tableName}`";
        
        try {
            $result = DB::query($sql);
            if (!$result) {
                return [];
            }
            
            $columns = $result->fetchAll(\PDO::FETCH_ASSOC);
            
            if (empty($columns)) {
                return [];
            }
            
            foreach ($columns as $column) {
                if (!isset($column['Field'])) {
                    continue;
                }
                
                $schema[$column['Field']] = [
                    'type' => $column['Type'] ?? '',
                    'null' => ($column['Null'] ?? 'NO') === 'YES',
                    'key' => $column['Key'] ?? '',
                    'default' => $column['Default'] ?? null,
                    'extra' => $column['Extra'] ?? ''
                ];
            }
        } catch (\PDOException $e) {
            // Table doesn't exist or other database error
            if (strpos($e->getMessage(), "doesn't exist") !== false || 
                strpos($e->getMessage(), "Table") !== false ||
                strpos($e->getMessage(), "42S02") !== false) {
                return [];
            }
            // Re-throw other database errors
            throw new \RuntimeException("Database error while fetching schema for table '$tableName': " . $e->getMessage(), 0, $e);
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
        
        $props = $entity->getProperties();

        // Get current schema
        $currentSchema = $this->getCurrentTableSchema($tableName);
        $modelSchema = $this->getModelSchema($entity);
        $schemaDiff = $this->compareSchemas($currentSchema, $modelSchema);

        // If table doesn't exist, create it
        if (empty($currentSchema)) {
            $createSql = Schema::create($tableName, function($table) use ($props) {
                foreach ($props as $prop) {
                    if ($attributes = $prop->getAttributes(Column::class)) {
                        $this->addColumnToBlueprint($table, $prop->getName(), $attributes[0]->newInstance());
                    }
                }
            });
            $addUpSqls[] = '$this->addSql("' . addslashes($createSql) . '");';
            $addDownSqls[] = '$this->addSql(Schema::drop(\'' . $tableName . '\'));';
            return;
        }

        // Table exists, process alterations
        if (!empty($schemaDiff['new']) || !empty($schemaDiff['modified'])) {
            $alterSql = Schema::alter($tableName, function($table) use ($schemaDiff) {
                // Add new columns
                if (!empty($schemaDiff['new'])) {
                    foreach ($schemaDiff['new'] as $columnName => $columnDef) {
                        $this->addColumnDefToBlueprint($table, $columnName, $columnDef);
                    }
                }
                // Note: Modify columns would need MODIFY COLUMN support in TableBlueprint
            });
            $addUpSqls[] = '$this->addSql("' . addslashes($alterSql) . '");';
        }

        if (!empty($schemaDiff['removed'])) {
            foreach ($schemaDiff['removed'] as $columnName) {
                $addUpSqls[] = '$this->addSql("ALTER TABLE `' . $tableName . '` DROP COLUMN `' . $columnName . '`");';
            }
        }
    }
    
    /**
     * Add a column to TableBlueprint from Column attribute
     */
    private function addColumnToBlueprint($table, string $name, $columnAttr): void
    {
        $type = $columnAttr->type->value;
        $length = $columnAttr->length;
        
        // Map type to TableBlueprint method
        switch(strtolower($type)) {
            case 'varchar':
            case 'string':
                $col = $table->varchar($name, $length ?? 255);
                break;
            case 'char':
                $col = $table->char($name, $length ?? 36);
                break;
            case 'int':
            case 'integer':
                $col = $table->int($name);
                break;
            case 'bigint':
                $col = $table->bigInteger($name);
                break;
            case 'text':
                $col = $table->text($name);
                break;
            case 'longtext':
                $col = $table->text($name); // TableBlueprint uses TEXT for both
                break;
            case 'decimal':
                $col = $table->decimal($name, 10, 2);
                break;
            case 'boolean':
                $col = $table->boolean($name);
                break;
            case 'timestamp':
                $col = $table->timestamp($name);
                break;
            case 'datetime':
                $col = $table->datetime($name);
                break;
            case 'date':
                $col = $table->date($name);
                break;
            case 'json':
                $col = $table->json($name);
                break;
            default:
                $col = $table->string($name, $length ?? 255);
                break;
        }
        
        // Apply modifiers
        if ($columnAttr->nullable) {
            $col->nullable();
        }
        if ($columnAttr->default !== null) {
            $col->default($columnAttr->default);
        }
        if ($columnAttr->comment) {
            $col->comment($columnAttr->comment);
        }
        if ($columnAttr->unique) {
            $col->unique();
        }
        if ($columnAttr->index) {
            $col->index();
        }
    }
    
    /**
     * Add a column definition to TableBlueprint from schema array
     */
    private function addColumnDefToBlueprint($table, string $name, array $columnDef): void
    {
        // Parse type from schema (e.g., "varchar(255)" -> "varchar", 255)
        $type = $columnDef['type'];
        $length = null;
        
        if (preg_match('/^(\w+)\((\d+)\)$/', $type, $matches)) {
            $type = $matches[1];
            $length = (int)$matches[2];
        }
        
        // Map type to TableBlueprint method
        switch(strtolower($type)) {
            case 'varchar':
                $col = $table->varchar($name, $length ?? 255);
                break;
            case 'char':
                $col = $table->char($name, $length ?? 36);
                break;
            case 'int':
                $col = $table->int($name);
                break;
            case 'bigint':
                $col = $table->bigInteger($name);
                break;
            case 'text':
                $col = $table->text($name);
                break;
            case 'decimal':
                $col = $table->decimal($name, 10, 2);
                break;
            case 'tinyint':
            case 'boolean':
                $col = $table->boolean($name);
                break;
            case 'timestamp':
                $col = $table->timestamp($name);
                break;
            case 'datetime':
                $col = $table->datetime($name);
                break;
            case 'date':
                $col = $table->date($name);
                break;
            case 'json':
                $col = $table->json($name);
                break;
            default:
                $col = $table->string($name, $length ?? 255);
                break;
        }
        
        // Apply modifiers
        if ($columnDef['null']) {
            $col->nullable();
        }
        if (isset($columnDef['default']) && $columnDef['default'] !== null) {
            $col->default($columnDef['default']);
        }
    }
}