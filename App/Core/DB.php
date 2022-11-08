<?php
namespace App\Core;
global $app;

$servername = App::getconfig('db')['host'];
$dbname = App::getconfig('db')['database'];
$username = App::getconfig('db')['username'];
$password = App::getconfig('db')['password'];
$charset = App::getConfig('db')['charset'];

define('DB_HOST', $servername);
define('DB_NAME', $dbname);
define('DB_USER', $username);
define('DB_PASS', $password);
define('DB_CHAR', $charset);

class DB
{
    protected static $instance = null;
    protected static $conn = null;
    protected static $sql = [];
    protected static $table = null;
    protected $values = [];

    protected function __construct() {}
    protected function __clone() {}

    public static function instance()
    {
        if (self::$conn === null)
        {
            $opt  = array(
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => FALSE,
            );
            $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHAR;
            self::$conn = new \PDO($dsn, DB_USER, DB_PASS, $opt);
        }
        return self::$conn;
    }

    public function run(array $args = [])
    {
        if (!self::$conn) {
            self::instance();
        }

        $sql = implode('', self::$sql);
        $exp = explode(' ', $sql);
        $type = array_shift($exp);
        $exp = null;

        if ($type == 'UPDATE' || $type == 'INSERT') {
            $args = array_merge($this->values, $args);
        }

        try {
            if (!$args) {
                return self::$conn->query($sql);
            }

            $stmt = self::$conn->prepare($sql);
            $check = $stmt->execute($args);

            self::$sql = [];
            if ($check) {
                return $stmt;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            echo $sql;
            var_dump($ex->getMessage());
        }

        self::$sql = [];
    }

    public static function Sql($sql = '') {
        self::$sql[] = $sql;
        return new self();
    }

    public function All($args = []) {
        if($result = $this->run($args)) {
            $data = $result->fetchAll(\PDO::FETCH_OBJ);

            if(is_array($data) && count($data) > 0) {
                return $data;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function Once($args = []) {
        if($result = $this->run($args)) {
            $data = $result->fetch(\PDO::FETCH_OBJ);

            if($data) {
                return $data;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function Select($args = []) {
        $columns = implode(',', $args);
        $columns = rtrim($columns, ',');

        if(empty($columns)) {
            $columns = '*';
        }

        self::$sql[] = "SELECT ". $columns ." FROM ". self::$table . " ";
        return self::$instance;
    }

    public function Where($criteria) {
        $keys = array_keys($criteria);
        $whereClause = '';
        foreach($keys as $key) {
            $whereClause .= " $key = ? AND ";
        }
        $whereClause = rtrim($whereClause, 'AND ');
        self::$sql[] = ' WHERE'.$whereClause;
        return $this->All(array_values($criteria));
    }

    public function WhereOnce($criteria) {
        $keys = array_keys($criteria);
        $whereClause = '';
        foreach($keys as $key) {
            $whereClause .= " $key = ? AND ";
        }
        $whereClause = rtrim($whereClause, 'AND ');
        self::$sql[] = ' WHERE'.$whereClause;
        return $this->Once(array_values($criteria));
    }

    public function Delete($args = []) {
        self::$sql[] = "DELETE FROM ". self::$table ." ";
        return self::$instance;
    }

    public function Update($args = []) {
        self::$sql[] = "UPDATE ". self::$table ." SET ";
        $query = '';
        foreach($args as $key => $value) {
            $query .= $key." = ?, ";
            array_push($this->values, $value);
        }

        self::$sql[] = rtrim($query, ', ');
        
        return self::$instance;
    }

    public function Insert($args = []) {
        self::$sql[] = "INSERT INTO ". self::$table ." ";
        $values = '(';
        $cols = '(';
        foreach($args as $key => $value) {
            $cols .= $key.", ";
            $values .= '?, ';
            array_push($this->values, $value);
        }

        $cols = rtrim($cols, ', '). ") ";
        $values = rtrim($values, ', '). ") ";

        $query = $cols." VALUES ". $values;


        self::$sql[] = rtrim($query, ',');
        return $this->run();
    }

    public static function Table($table = '') {
        if(empty($table)) return false;

        self::$table = $table;

        if(!self::$instance) {
            self::$instance = new self();
        }

        if(!self::$conn) {
            self::instance();
        }
        self::$sql = [];
        return self::$instance;
    }

    public static function Query($sql, $params = []) {
        return [$sql => $params];
    }
}