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
        $this->db = DB::create();
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
        try {
            $results = $this->db->table($this->table)->select()->where($criteria)->get();
            return $results ? $this->hydrate($results[0]) : null;
        } catch (\Throwable $e) {
            throw new SwidlyException("Error finding record: " . $e->getMessage(), 500, $e);
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
    public function findAll(int $limit = 100, array $criteria = []): array
    {
        try {
            $query = $this->db->table($this->table)->select();

            if (!empty($criteria)) {
                $query->where($criteria);
            }

            $results = $query->limit($limit)->get();

            return $results ? array_map(fn($row) => $this->hydrate($row), $results) : [];
        } catch (\Throwable $e) {
            throw new SwidlyException("Error fetching records: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Save the current model to the database (insert or update).
     *
     * @return bool True on success
     * @throws SwidlyException If a database error occurs
     */
    public function save(): bool
    {
        try {
            $data = $this->extractData();

            if ($this->isNewRecord($data)) {
                // Insert new record
                $id = $this->db->table($this->table)->insert($data);

                // Update the ID in the current object if available
                if ($this->idField && $id) {
                    $setter = 'set' . ucfirst($this->idField);
                    if (method_exists($this, $setter)) {
                        $this->$setter($id);
                    }
                }
            } else {
                // Update existing record
                $criteria = [$this->idField => $data[$this->idField]];
                unset($data[$this->idField]); // Don't update the ID field
                $this->db->table($this->table)->update($data)->where($criteria)->get();
            }

            return true;
        } catch (\Throwable $e) {
            throw new SwidlyException("Error saving record: " . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Remove the current model from the database.
     *
     * @return bool True on success
     * @throws SwidlyException If the ID field is missing or a database error occurs
     */
    public function remove(): bool
    {
        try {
            $data = $this->extractData();

            if (!isset($data[$this->idField]) || empty($this->idField)) {
                throw new SwidlyException("Cannot remove record: missing ID field", 400);
            }

            $this->db->table($this->table)->where([$this->idField => $data[$this->idField]])->delete();
            return true;
        } catch (\Throwable $e) {
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

        // Add ID field if available
        if ($this->idField) {
            $idGetter = 'get' . ucfirst($this->idField);
            if (method_exists($this, $idGetter)) {
                $data[$this->idField] = $this->$idGetter();
            }
        }

        // Add all other properties
        foreach ($this->props as $prop => $obj) {
            if (isset($obj['getter']) && method_exists($this, $obj['getter'])) {
                $value = $this->{$obj['getter']}();
                $data[$prop] = $value !== null ? $value : null;
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
            $setter = 'set' . ucfirst($column);

            if ($column === $this->idField) {
                // Set ID directly or use setter if available
                if (method_exists($class, $setter)) {
                    $class->$setter($value);
                } else {
                    $class->$column = $value;
                }
            } elseif (method_exists($class, $setter)) {
                // Handle property mapping if configured
                if (isset($this->props[$column]['mapping']) && $this->props[$column]['mapping']) {
                    $map = new $this->props[$column]['mapping']();
                    // Map value (assuming there's a method to do this)
                    $value = $map->find(['id' => $value]);
                }

                $class->$setter($value);
            }
        }

        return $class;
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
                'getter' => 'get' . ucfirst($propertyName),
                'setter' => 'set' . ucfirst($propertyName),
                'mapping' => $columnInstance->mapping ?? null
            ];

            if ($columnInstance->isPrimary ?? false) {
                $properties['idField'] = $propertyName;
            }
        }

        return $properties;
    }
}