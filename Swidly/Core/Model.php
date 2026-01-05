<?php

declare(strict_types=1);

namespace Swidly\Core;

use Swidly\Core\Attributes\Column;
use Swidly\Core\Attributes\Table;
use ReflectionClass;
use Swidly\Core\DB;

class Model
{
    protected ?object $app;
    protected string $class;
    private array $vars = [];
    private array $props = [];
    private ?string $table = null;
    private ?string $idField = null;
    private ReflectionClass $reflectionClass;
    private DB $db;

    public function __construct()
    {
        global $app;
        $this->class = get_called_class();
        $this->app = $app;

        $this->reflectionClass = new ReflectionClass($this->class);
        $this->table = $this->getTableProperty();

        $properties = $this->getColumnProperties();
        $this->idField = $properties['idField'] ?? null;
        unset($properties['idField']);
        $this->props = $properties;
        $this->db = DB::getInstance();
    }

    /**
     * Get a model instance by name
     * @param string $model The model name to load
     * @return Model|null The model instance or null if not found
     * @throws \RuntimeException If model loading fails
     */
    static function load(string $model): ?Model 
    {
        try {
            // Get and validate theme path
            $themePath = self::validateThemePath();
            
            // Get and validate models directory
            $modelDir = self::getModelsDirectory($themePath);
    
            // Build and check model file path
            $modelFile = $modelDir . '/' . $model . '.php';
            if (!file_exists($modelFile)) {
                return null;
            }

            // Load and validate model class
            $modelClass = self::loadModelClass($modelFile, $model);
            if ($modelClass === null) {
                return null;
            }

            // Instantiate and validate model
            return self::instantiateModel($modelClass);

        } catch (\Exception $e) {
            error_log("Error loading model '$model': " . $e->getMessage());
            throw new \RuntimeException(
                "Failed to load model '$model': " . $e->getMessage(), 
                0, 
                $e
            );
        }
    }

    /**
     * Validate theme path and return it
     * @throws \RuntimeException
     */
    private static function validateThemePath(): string 
    {
        $themePath = Swidly::theme()['base'] ?? '';
        if (empty($themePath) || !is_dir($themePath)) {
            throw new \RuntimeException("Invalid theme path");
        }
        return $themePath;
    }

    /**
     * Get and validate models directory
     * @throws \RuntimeException
     */
    private static function getModelsDirectory(string $themePath): string 
    {
        $modelDirMatches = glob($themePath . '/models');
        if (empty($modelDirMatches) || !is_dir($modelDirMatches[0])) {
            throw new \RuntimeException("Models directory not found");
        }
        return $modelDirMatches[0];
    }

    /**
     * Load and validate model class
     * @return string|null Fully qualified class name or null if not found
     */
    private static function loadModelClass(string $modelFile, string $model): ?string 
    {
        $beforeClasses = get_declared_classes();
        require_once $modelFile;
        $newClasses = array_diff(get_declared_classes(), $beforeClasses);

        // First try to find in newly declared classes
        foreach ($newClasses as $class) {
            if (self::isMatchingClass($class, $model)) {
                return $class;
            }
        }

        // Fallback: search in all classes
        foreach (get_declared_classes() as $class) {
            if (self::isMatchingClass($class, $model)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Check if class name matches the model name
     */
    private static function isMatchingClass(string $class, string $model): bool 
    {
        $className = (strpos($class, '\\') !== false) 
            ? substr($class, strrpos($class, '\\') + 1) 
            : $class;
        return $className === $model;
    }

    /**
     * Instantiate and validate model class
     * @throws \RuntimeException
     */
    private static function instantiateModel(string $modelClass): Model 
    {
        $instance = new $modelClass();
        if (!($instance instanceof Model)) {
            throw new \RuntimeException("Class '$modelClass' is not a Model instance");
        }
        return $instance;
    }

    /**
     * Find a single record by arbitrary criteria.
     *
     * @param array $criteria Key-value pairs to search by
     * @return static|null The found model instance or null if not found
     * @throws SwidlyException If a database error occurs
     */
    public function find(array $criteria): ?static
    {
        if (empty($this->table)) {
            throw new SwidlyException("No table defined for model " . get_class($this));
        }

        try {
            $this->validateCriteria($criteria);
            $results = $this->db->table($this->table)
                ->select()
                ->where($criteria)
                ->limit(1)
                ->get();

            return !empty($results) ? $this->hydrate($results[0]) : null;
        } catch (\Throwable $e) {
            error_log("[Model Error] Find failed for table {$this->table}: " . $e->getMessage());
            throw new SwidlyException("Error finding record: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Validate search criteria
     * @throws SwidlyException
     */
    private function validateCriteria(array $criteria): void 
    {
        if (empty($criteria)) {
            throw new SwidlyException("Search criteria cannot be empty");
        }

        foreach ($criteria as $field => $value) {
            if (!is_string($field)) {
                throw new SwidlyException("Invalid field name in criteria");
            }
            // Validate field exists in model
            if (!isset($this->props[$field]) && $field !== $this->idField) {
                throw new SwidlyException("Unknown field '{$field}' in criteria");
            }
        }
    }

    /**
     * Retrieve all records with optional limit.
     *
     * @param int $limit Maximum number of records to retrieve
     * @param array $criteria Optional filtering criteria
     * @return array Array of model instances
     * @throws SwidlyException If a database error occurs
     */
    public function findAll(array $criteria = [], int $limit = 100, int $offset = 0): array
    {
        if (empty($this->table)) {
            throw new SwidlyException("No table defined for model " . get_class($this));
        }

        if ($limit < 0) {
            throw new SwidlyException("Limit cannot be negative");
        }

        if ($offset < 0) {
            throw new SwidlyException("Offset cannot be negative");
        }

        try {
            if (!empty($criteria)) {
                $this->validateCriteria($criteria);
            }

            $query = $this->db->table($this->table)
                ->select();

            if (!empty($criteria)) {
                $query->where($criteria);
            }

            $results = $query
                ->limit($limit, $offset)
                ->get();

            $models = !empty($results) 
                ? array_map(fn($row) => $this->hydrate($row), $results) 
                : [];

            // Return JSON if Accept header indicates JSON request
            if (Swidly::isRequestJson()) {
                return $this->formatAsJson($models);
            }

            return $models;

        } catch (\Throwable $e) {
            error_log("[Model Error] FindAll failed for table {$this->table}: " . $e->getMessage());
            throw new SwidlyException("Error fetching records: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Check if the request expects JSON response
     * @return bool
     */
    private function isJsonRequest(): bool
    {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($acceptHeader, 'application/json') !== false;
    }

    /**
     * Format model instances as JSON-compatible array
     * @param array $models Array of Model instances
     * @return array JSON-formatted data
     */
    private function formatAsJson(array $models): array
    {
        return array_map(function($model) {
            $data = [];
            foreach ($this->props as $prop => $obj) {
                if (property_exists($model, $prop)) {
                    try {
                        $reflection = new \ReflectionProperty($model, $prop);
                        if ($reflection->isInitialized($model)) {
                            $data[$prop] = $model->$prop;
                        }
                    } catch (\ReflectionException $e) {
                        // Skip inaccessible properties
                    }
                }
            }
            // Add ID field
            if ($this->idField && property_exists($model, $this->idField)) {
                try {
                    $reflection = new \ReflectionProperty($model, $this->idField);
                    if ($reflection->isInitialized($model)) {
                        $data[$this->idField] = $model->{$this->idField};
                    }
                } catch (\ReflectionException $e) {
                    // Skip inaccessible ID field
                }
            }
            return $data;
        }, $models);
    }

    /**
     * Save the current model to the database (insert or update).
     *
     * @return bool True on success
     * @throws SwidlyException If a database error occurs
     */
    public function save(): int | string
    {
        if (empty($this->table)) {
            throw new SwidlyException("No table defined for model " . get_class($this));
        }

        try {
            $data = $this->extractData();

            // Validate data before saving
            $this->validateData($data);

            if ($this->isNewRecord($data)) {
                // Insert new record

                $this->db->table($this->table)->insert($data);
                $id = (int)$this->db->lastInsertId();
                if (!$id) {
                    throw new SwidlyException("Failed to insert new record");
                }

                // Update the ID in the current object if available
                if ($this->idField && property_exists($this, $this->idField)) {
                    $this->{$this->idField} = $id;
                }
            } else {
                if (empty($this->idField)) {
                    throw new SwidlyException("Cannot update record: no ID field defined");
                }

                // Update existing record
                $criteria = [$this->idField => $data[$this->idField]];
                unset($data[$this->idField]); // Don't update the ID field

                // Verify record exists before update
                $exists = $this->db->table($this->table)
                    ->select()
                    ->where($criteria)
                    ->get();

                if (empty($exists)) {
                    throw new SwidlyException("Record not found for update");
                }

                $this->db->table($this->table)
                    ->update($data)
                    ->where($criteria)
                    ->get();
            }

            return $id ?? $this->idField;

        } catch (\Throwable $e) {
            $context = [
                'table' => $this->table,
                'modelClass' => get_class($this),
                'error' => $e->getMessage()
            ];
            dd("[Model Error] Save failed: " . json_encode($context));
            throw new SwidlyException("Error saving record: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Validate data before saving
     * @throws SwidlyException
     */
    private function validateData(array $data): void
    {
        if (empty($data)) {
            throw new SwidlyException("No data to save");
        }

        foreach ($data as $field => $value) {
            // Skip ID field validation for new records
            if ($field === $this->idField && $this->isNewRecord($data)) {
                continue;
            }
            
            // Validate field exists in model
            if (!isset($this->props[$field]) && $field !== $this->idField) {
                throw new SwidlyException("Unknown field '{$field}' in data");
            }

            // Validate mapped fields
            if (isset($this->props[$field]['mapping']) && $value !== null) {
                if (!is_object($value) || !($value instanceof Model)) {
                    throw new SwidlyException("Invalid mapped value for field '{$field}'");
                }
            }
        }

        // Check for required fields (based on your database schema)
        // You might want to add schema information to the Column attribute
        // and check it here
    }

    /**
     * Remove the current model from the database.
     *
     * @return bool True on success
     * @throws SwidlyException If the ID field is missing or a database error occurs
     */
    public function remove(): bool
    {
        if (empty($this->table)) {
            throw new SwidlyException("No table defined for model " . get_class($this));
        }

        try {
            $data = $this->extractData();

            if (empty($this->idField)) {
                throw new SwidlyException("Cannot remove record: no ID field defined", 400);
            }

            if (!isset($data[$this->idField])) {
                throw new SwidlyException("Cannot remove record: missing ID value", 400);
            }

            // Verify record exists before deletion
            $exists = $this->db->table($this->table)
                ->select()
                ->where([$this->idField => $data[$this->idField]])
                ->get();

            if (empty($exists)) {
                throw new SwidlyException("Record not found for deletion", 404);
            }

            $this->db->table($this->table)
                ->where([$this->idField => $data[$this->idField]])
                ->delete();

            return true;
        } catch (\Throwable $e) {
            $context = [
                'table' => $this->table,
                'modelClass' => get_class($this),
                'id' => $data[$this->idField] ?? null,
                'error' => $e->getMessage()
            ];
            error_log("[Model Error] Remove failed: " . json_encode($context));
            throw new SwidlyException("Error removing record: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Check if the current record is new (not yet saved to database).
     *
     * @param array $data Extracted model data
     * @return bool True if this is a new record
     */
    private function isNewRecord(array $data): bool
    {
        return empty($this->idField) ||
            !isset($data[$this->idField]) ||
            empty($data[$this->idField]) ||
            (int)$data[$this->idField] === 0;
    }

    /**
     * Extract column data from the object properties.
     *
     * @return array Data ready for database operations
     */
    private function extractData(): array
    {
        $data = [];

        // Add ID field if available and initialized
        if ($this->idField && property_exists($this, $this->idField)) {
            try {
                $reflection = new \ReflectionProperty($this, $this->idField);
                if ($reflection->isInitialized($this)) {
                    $data[$this->idField] = $this->{$this->idField};
                }
            } catch (\ReflectionException $e) {
                // Property doesn't exist or can't be accessed
            }
        }

        // Add all other properties
        foreach ($this->props as $prop => $obj) {
            if (property_exists($this, $prop)) {
                try {
                    $reflection = new \ReflectionProperty($this, $prop);
                    if (!$reflection->isInitialized($this)) {
                        continue; // Skip uninitialized properties
                    }
                    $value = $this->$prop;

                    if ($obj['mapping'] ?? false) {
                        // If property has mapping, store the related object's ID
                        if ($value instanceof Model) {
                            $mappingIdField = $this->props[$prop]['mappingIdField'] ?? 'id';
                            try {
                                $mappingReflection = new \ReflectionProperty($value, $mappingIdField);
                                if ($mappingReflection->isInitialized($value)) {
                                    $value = $value->$mappingIdField;
                                } else {
                                    $value = null;
                                }
                            } catch (\ReflectionException $e) {
                                $value = null;
                            }
                            $newKey = $prop . 'Id';
                            $this->props[$newKey] = $this->props[$prop];
                            $this->props[$newKey]['mapping'] = null; // Prevent recursive mapping
                            unset($this->props[$prop]);
                            $prop = $newKey;
                        } else {
                            $value = null;
                        }
                    }
                    $data[$prop] = $value !== null ? $value : null;
                } catch (\ReflectionException $e) {
                    // Skip properties that can't be reflected
                    continue;
                }
            }
        }

        return $data;
    }

    /**
     * Hydrate the model object with data.
     *
     * @param \stdClass $data Data from the database
     * @return static Populated model instance
     */
    private function hydrate(\stdClass $data): static
    {
        $class = new static();

        foreach ($data as $column => $value) {
            // Validate column name to prevent injection
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                error_log("[Model Warning] Skipping invalid column name: " . $column);
                continue;
            }

            try {
                if ($column === $this->idField) {
                    // Set ID directly if property exists
                    if (property_exists($class, $column)) {
                        $class->$column = $this->castToPropertyType($class, $column, $value);
                    }
                } elseif (property_exists($class, $column)) {
                    // Handle property mapping if configured
                    if (isset($this->props[$column]['mapping']) && $this->props[$column]['mapping']) {
                        if ($value !== null) {
                            try {
                                $mapClass = $this->props[$column]['mapping'];
                                if (!class_exists($mapClass)) {
                                    throw new SwidlyException("Mapping class {$mapClass} not found");
                                }
                                $map = new $mapClass();
                                if (!method_exists($map, 'find')) {
                                    throw new SwidlyException("Mapping class {$mapClass} missing find method");
                                }
                                $value = $map->find(['id' => $value]);
                            } catch (\Throwable $e) {
                                error_log("[Model Error] Mapping failed for column {$column}: " . $e->getMessage());
                                $value = null;
                            }
                        }
                    }

                    $class->$column = $this->castToPropertyType($class, $column, $value);
                }
            } catch (\Throwable $e) {
                error_log("[Model Error] Hydration failed for column {$column}: " . $e->getMessage());
                // Continue with next column
            }
        }

        return $class;
    }

    /**
     * Cast a value to match the property's declared type
     * @param object $object The object containing the property
     * @param string $property The property name
     * @param mixed $value The value to cast
     * @return mixed The cast value
     */
    private function castToPropertyType(object $object, string $property, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        try {
            $reflection = new \ReflectionProperty($object, $property);
            $type = $reflection->getType();

            if ($type === null) {
                return $this->sanitizeValue($value);
            }

            // Handle union types by getting the first non-null type
            if ($type instanceof \ReflectionUnionType) {
                $types = $type->getTypes();
                foreach ($types as $t) {
                    if ($t->getName() !== 'null') {
                        $typeName = $t->getName();
                        break;
                    }
                }
            } else {
                $typeName = $type->getName();
            }

            // Cast based on type
            return match ($typeName ?? 'string') {
                'int' => (int) $value,
                'float' => (float) $value,
                'bool' => (bool) $value,
                'string' => $this->sanitizeValue((string) $value),
                'array' => is_array($value) ? $value : [$value],
                default => $value instanceof Model ? $value : $this->sanitizeValue($value),
            };
        } catch (\ReflectionException $e) {
            return $this->sanitizeValue($value);
        }
    }

    /**
     * Sanitize a value before setting it in the model
     * @param mixed $value The value to sanitize
     * @return mixed The sanitized value
     */
    private function sanitizeValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            // Remove null bytes and other potentially harmful characters
            $value = str_replace(["\0", "\r", "\x1a"], '', $value);
            
            // Convert special characters to HTML entities
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            return $value;
        }

        if (is_array($value)) {
            return array_map([$this, 'sanitizeValue'], $value);
        }

        if (is_object($value) && !($value instanceof Model)) {
            // Convert objects to arrays, except for Model instances
            return $this->sanitizeValue((array)$value);
        }

        return $value;
    }

    /**
     * Get the table name from the Table attribute.
     *
     * @return string|null Table name or null if not defined
     */
    private function getTableProperty(): ?string
    {
        $tableAttribute = $this->reflectionClass->getAttributes(Table::class);

        if (empty($tableAttribute)) {
            return null;
        }

        $tableInstance = $tableAttribute[0]->newInstance();
        return $tableInstance->name;
    }

    /**
     * Get properties marked with the Column attribute.
     *
     * @return array Column properties with their configurations
     */
    private function getColumnProperties(): array
    {
        $properties = [];
        $idField = null;

        foreach ($this->reflectionClass->getProperties() as $property) {
            $columnAttributes = $property->getAttributes(Column::class);

            if (empty($columnAttributes)) {
                continue;
            }

            $columnInstance = $columnAttributes[0]->newInstance();
            $propertyName = $property->getName();

            $properties[$propertyName] = [
                'mapping' => $columnInstance->mapping ?? null
            ];

            if ($columnInstance->isPrimary ?? false) {
                $properties['idField'] = $propertyName;
            }
        }

        return $properties;
    }
}