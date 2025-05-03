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
        ];
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHAR);

        try {
            $conn = new \PDO($dsn, DB_USER, DB_PASS, $opt);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Database connection error: ' . $e->getMessage());
        }

        return new self($conn);
    }

    public function table(string $table): DB
    {
        $this->queryParts['table'] = "$table";
        return $this;
    }

    public function select(array $columns = ['*']): DB
    {
        $this->queryParts['select'] = 'SELECT ' . implode(', ', $columns) . 'FROM ';
        return $this;
    }

    public function where(array $criteria): DB
    {
        $whereClause = implode(' AND ', array_map(fn($key) => "$key = ?", array_keys($criteria)));
        $this->queryParts['where'] = "WHERE $whereClause";
        foreach ($criteria as $key => $value) {
            $this->values[] = $value;
        }

        return $this;
    }

    // Add limit method for paginating or limiting query results.
    public function limit(int $limit, int $offset = 0): DB
    {
        $this->queryParts['limit'] = "LIMIT $offset, $limit";
        return $this;
    }

    public function get(): array|false
    {
        $sql = $this->buildQuery();
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($this->values);

        $this->queryParts = []; // Reset query parts after execution
        $this->values = []; // Reset values after execution

        return $stmt->fetchAll(\PDO::FETCH_OBJ) ?: false;
    }

    public function printSQL() {
        $sql = $this->buildQuery();
        dump($sql);
    }

    public function insert(array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->queryParts['table']} ($columns) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(array_values($data));
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

    private function buildQuery(): string
    {
        // Ensure query parts are concatenated in the defined order
        $orderedParts = ['select', 'update', 'insert', 'delete', 'table', 'where', 'limit'];

        $query = '';
        foreach ($orderedParts as $part) {
            if (!empty($this->queryParts[$part])) {
                $query .= ' ' . $this->queryParts[$part];
            }
        }

        return trim($query); // Remove any trailing spaces
    }

    /**
     * Execute a SQL query with prepared statements
     *
     * @param string $sql The SQL query to execute
     * @param array $params Parameters to bind to the query
     * @return \PDOStatement|false The prepared and executed statement
     * @throws \PDOException If the query preparation or execution fails
     */
    public static function query(string $sql, array $params = []): \PDOStatement|false {
        try {
            // Check if connection is established
           $conn = self::create()->conn;
            if (!$conn) {
                throw new \RuntimeException('Database connection not established.');
            }
            // Check if SQL statement is valid
            if (empty($sql)) {
                throw new \InvalidArgumentException('SQL statement cannot be empty.');
            }
            // Check if parameters are valid
            if (!is_array($params)) {
                throw new \InvalidArgumentException('Parameters must be an array.');
            }
            // Check if parameters are not empty
            foreach ($params as $param) {
                if (empty($param)) {
                    throw new \InvalidArgumentException('Parameters cannot be empty.');
                }
            }
            // Prepare statement
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                throw new \PDOException("Failed to prepare SQL statement: " . implode(' ', $conn->errorInfo()));
            }

            // Execute with parameters
            if (!$stmt->execute($params)) {
                throw new \PDOException("Failed to execute SQL statement: " . implode(' ', $stmt->errorInfo()));
            }

            return $stmt;
        } catch (\PDOException $e) {
            // You could log the error here
            // Logger::log($e->getMessage());

            // Re-throw the exception for the caller to handle
            throw $e;
        }
    }

}