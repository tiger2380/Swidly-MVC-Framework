<?php
namespace Swidly\Core;
global $app;

$servername = Swidly::getconfig('db::host');
$dbname = Swidly::getconfig('db::database');
$username = Swidly::getconfig('db::username');
$password = Swidly::getconfig('db::password');
$charset = Swidly::getConfig('db::charset');

// Check if the database connection details are set
if (empty($servername) || empty($dbname) || empty($username) || empty($password)) {
    throw new \RuntimeException('Database connection details are not set in the configuration.');
}
// Check if the database connection details are valid
if (!is_string($servername) || !is_string($dbname) || !is_string($username) || !is_string($password)) {
    throw new \RuntimeException('Database connection details must be strings.');
}
// Check if the charset is set and valid
define('DB_HOST', $servername);
define('DB_NAME', $dbname);
define('DB_USER', $username);
define('DB_PASS', $password);
define('DB_CHAR', $charset);

class DB
{
    private \PDO $conn;
    private array $queryParts = [];
    private array $values = [];
    private static ?DB $instance = null;
    private int $transactionDepth = 0;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public static function create(): DB
    {
        $opt = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_TIMEOUT => 5,  // 5 seconds timeout
            \PDO::ATTR_PERSISTENT => false, // Disable persistent connections
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;",
            \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
        ];
        
        // Add SSL options if SSL is configured
        if (Swidly::getConfig('db::ssl_enabled', false)) {
            $opt[\PDO::MYSQL_ATTR_SSL_CA] = Swidly::getConfig('db::ssl_ca');
            $opt[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHAR);

        try {
            $conn = new \PDO($dsn, DB_USER, DB_PASS, $opt);
            // Set session variables for security
            $conn->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        } catch (\PDOException $e) {
            throw new \RuntimeException('Database connection error: ' . $e->getMessage());
        }

        return new self($conn);
    }

    public static function getInstance(): DB
    {
        if (self::$instance === null) {
            self::$instance = self::create();
        }
        return self::$instance;
    }

    public function table(string $table): DB
    {
        $this->queryParts['table'] = "$table";
        return $this;
    }

    public function select(array $columns = ['*']): DB
    {
        $this->queryParts['select'] = 'SELECT ' . implode(', ', $columns) . ' FROM ';
        return $this;
    }

    public function where(array $criteria): DB
    {
        $whereParts = [];
        foreach ($criteria as $key => $value) {
            if (is_null($value)) {
                $whereParts[] = "$key IS NULL";
            } elseif (is_array($value) && isset($value['op']) && strtolower($value['op']) === 'not_null') {
                $whereParts[] = "$key IS NOT NULL";
            } else {
                $whereParts[] = "$key = ?";
                $this->values[] = $value;
            }
        }
        $this->queryParts['where'] = " WHERE " . implode(' AND ', $whereParts);

        return $this;
    }

    // Add limit method for paginating or limiting query results.
    public function limit(int $limit, int $offset = 0): DB
    {
        $this->queryParts['limit'] = "LIMIT $offset, $limit";
        return $this;
    }

    public function orderBy(string|array $columns, string $direction = 'ASC'): DB
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new \InvalidArgumentException('Order direction must be ASC or DESC');
        }

        if (is_string($columns)) {
            $this->queryParts['orderBy'] = "ORDER BY $columns $direction";
        } else {
            $orderParts = [];
            foreach ($columns as $column => $dir) {
                $dir = strtoupper($dir);
                if (!in_array($dir, ['ASC', 'DESC'])) {
                    throw new \InvalidArgumentException('Order direction must be ASC or DESC');
                }
                $orderParts[] = "$column $dir";
            }
            $this->queryParts['orderBy'] = "ORDER BY " . implode(', ', $orderParts);
        }

        return $this;
    }

    public function join(string $table, string $on, string $type = 'INNER'): DB
    {
        $joinClause = strtoupper($type) . " JOIN $table ON $on";
        if (!isset($this->queryParts['join'])) {
            $this->queryParts['join'] = [];
        }
        $this->queryParts['join'][] = $joinClause;
        return $this;
    }

    public function get(): array|false
    {
        $sql = $this->buildQuery();
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($this->values);

        $this->queryParts = []; // Reset query parts after execution
        $this->values = []; // Reset values after execution

        return $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
    }

    public function printSQL() {
        $sql = $this->buildQuery();
        return $sql;
    }

    public function insert(array $data): bool
    {
        try {
            $data = array_filter($data, fn($value) => !empty($value) && $value !== null);
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO {$this->queryParts['table']} ($columns) VALUES ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute(array_values($data));
        } catch (\PDOException $e) {
            throw new \RuntimeException('Insert failed: ' . $e->getMessage());
        }
    }

    public function update(array $data): self
    {
        if (!isset($this->queryParts['table'])) {
            throw new \RuntimeException('No table specified for update');
        }

        if (empty($data)) {
            throw new \RuntimeException('No data provided for update');
        }

        unset($data['id']);
        $setClause = implode(', ', array_map(fn($key) => "$key = ?", array_keys($data)));
        foreach ($data as $key => $value) {
            $this->values[] = $value;
        }
        $this->queryParts['update'] = "UPDATE {$this->queryParts['table']} SET $setClause";
        unset($this->queryParts['table']);

        return $this;
    }

    public function delete(): bool
    {
        if (!isset($this->queryParts['table'])) {
            throw new \RuntimeException('No table specified for delete');
        }

        if (!isset($this->queryParts['where'])) {
            throw new \RuntimeException('A WHERE clause is required for delete to prevent removing all rows');
        }

        $this->queryParts['delete'] = "DELETE FROM {$this->queryParts['table']}";

        $sql = implode(' ', $this->queryParts);

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($this->values);
    }

    public function saveJSON(string $field, array $data, int $id): bool {
        if (empty($field) || empty($data)) {
            throw new \InvalidArgumentException('Field name and data are required');
        }
        
        if (!isset($this->queryParts['table'])) {
            throw new \RuntimeException('No table specified for JSON save');
        }
        
        $jsonData = json_encode($data, JSON_THROW_ON_ERROR);
        $query = "UPDATE {$this->queryParts['table']} SET {$field} = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$jsonData, $id]);
    }

    public function getJSON(int $id, string $field): ?array {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid ID provided');
        }
        if (empty($field)) {
            throw new \InvalidArgumentException('Field name is required');
        }
        if (!isset($this->queryParts['table'])) {
            throw new \RuntimeException('No table specified for JSON retrieval');
        }

        $query = "SELECT {$field} FROM {$this->queryParts['table']} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }

        $jsonData = json_decode($result[$field], true, 512, JSON_THROW_ON_ERROR);
        return $jsonData;
    }

    public function updateJsonFields(int $id, string $field, array $data): bool {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid ID provided');
        }
        if (empty($field)) {
            throw new \InvalidArgumentException('Field name is required');
        }
        if (empty($data)) {
            throw new \InvalidArgumentException('Data array cannot be empty');
        }
        if (!isset($this->queryParts['table'])) {
            throw new \RuntimeException('No table specified for JSON update');
        }

        try {
            $this->conn->beginTransaction();

            $query = "SELECT {$field} FROM {$this->queryParts['table']} WHERE id = ? FOR UPDATE";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$result) {
                throw new \RuntimeException('Record not found');
            }

            $currentData = json_decode($result[$field] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($currentData)) {
                $currentData = [];
            }

            // Recursively merge new data with existing data
            $mergedData = $this->arrayMergeRecursive($currentData, $data);
            
            // Update the entire JSON object
            $query = "UPDATE {$this->queryParts['table']} SET {$field} = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([json_encode($mergedData, JSON_THROW_ON_ERROR), $id]);

            $this->conn->commit();
            return $result;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw new \RuntimeException('Failed to update JSON fields: ' . $e->getMessage());
        }
    }

    private function arrayMergeRecursive($arr1, $arr2) {
        foreach ($arr2 as $key => $value) {
            if (is_array($value) && isset($arr1[$key]) && is_array($arr1[$key])) {
                $arr1[$key] = $this->arrayMergeRecursive($arr1[$key], $value);
            } else {
                $arr1[$key] = $value;
            }
        }
        return $arr1;
    }

    private function buildQuery(): string
    {
        $orderedParts = ['select', 'update', 'insert', 'delete', 'table', 'join', 'where', 'orderBy', 'limit'];
        $query = '';
        foreach ($orderedParts as $part) {
            if (!empty($this->queryParts[$part])) {
                if ($part === 'join' && is_array($this->queryParts[$part])) {
                    $query .= ' ' . implode(' ', $this->queryParts[$part]);
                } else {
                    $query .= ' ' . $this->queryParts[$part];
                }
            }
        }
        return trim($query);
    }

    /**
     * Execute a SQL query with prepared statements
     *
     * @param string $sql The SQL query to execute
     * @param array $params Parameters to bind to the query
     * @return \PDOStatement|false The prepared and executed statement
     * @throws \PDOException If the query preparation or execution fails
     */
    public static function query(string $sql, array $params = []): \PDOStatement {
        try {
            // Check if SQL statement is valid
            if (empty($sql)) {
                throw new \InvalidArgumentException('SQL statement cannot be empty');
            }

            // Basic SQL injection prevention
            $dangerousPatterns = [
                '/;\s*DROP\s+/i',
                '/;\s*DELETE\s+/i',
                '/;\s*UPDATE\s+/i',
                '/;\s*INSERT\s+/i',
                '/;\s*ALTER\s+/i',
                '/;\s*TRUNCATE\s+/i'
            ];
            
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $sql)) {
                    throw new \InvalidArgumentException('Potentially dangerous SQL detected');
                }
            }

            // Get singleton connection
            $db = self::getInstance();
            $conn = $db->conn;

            // Determine if this is a write operation
            $isWriteOperation = preg_match('/^\s*(INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|ALTER|TRUNCATE)/i', $sql);

            // Only start transaction if not already in one
            if ($isWriteOperation && !$conn->inTransaction()) {
                $conn->beginTransaction();
                $db->transactionDepth = 1;
            } elseif ($isWriteOperation) {
                $db->transactionDepth++;
            }

            // Prepare statement
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                throw new \PDOException("Failed to prepare SQL statement");
            }

            // Bind parameters with type checking
            foreach ($params as $key => $value) {
                $type = match(gettype($value)) {
                    'boolean' => \PDO::PARAM_BOOL,
                    'integer' => \PDO::PARAM_INT,
                    'NULL' => \PDO::PARAM_NULL,
                    default => \PDO::PARAM_STR,
                };
                
                if (is_int($key)) {
                    $stmt->bindValue($key + 1, $value, $type);
                } else {
                    $stmt->bindValue($key, $value, $type);
                }
            }

            // Execute with timeout
            $timeout = 30; // seconds
            set_time_limit($timeout);
            
            if (!$stmt->execute()) {
                throw new \PDOException("Failed to execute SQL statement: " . implode(' ', $stmt->errorInfo()));
            }

            // Only commit if this was the last query in the transaction
            if ($isWriteOperation) {
                $db->transactionDepth--;
                if ($db->transactionDepth <= 0) {
                    if ($conn->inTransaction()) {
                        $conn->commit();
                    }
                    $db->transactionDepth = 0;
                }
            }

            return $stmt;
        } catch (\PDOException $e) {
            if (isset($db) && isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
                $db->transactionDepth = 0;
            }
            // Log the error with backtrace
            error_log(sprintf(
                "Database error: %s\nSQL: %s\nParams: %s\nTrace: %s",
                $e->getMessage(),
                $sql,
                json_encode($params),
                $e->getTraceAsString()
            ));

            throw new \RuntimeException('A database error occurred. Please check the error logs.');
        } catch (\Throwable $e) {
            if (isset($db) && isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
                $db->transactionDepth = 0;
            }
            error_log("Unexpected error in database query: " . $e->getMessage());
            throw new \RuntimeException('An unexpected error occurred. Please check the error logs.');
        }
    }

    public function lastInsertId(): string
    {
        return $this->conn->lastInsertId();
    }
}