#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Swidly\Core\Console;

use Swidly\Core\Factory\CommandFactory;

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/formatPrint.php';
define('ROOTDIR', dirname(dirname(__DIR__)));
require ROOTDIR . '/../bootstrap.php';
require_once ROOTDIR . '/Core/helpers.php';

class Console {
    private const ROUTE_TEMPLATE = <<<'STR'
$this->get('%s%s', '%sController::Index')
->name('%s');
STR;


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
        %s
    }

    public function down(): void
    {
        %s
    }
}
STR;

    private array $args;
    private array $opts;
    private array $theme;

    public function __construct(array $args) {
        $this->opts = getopt('mcud', ['model::', 'controller::', 'up::', 'down::'], $rest_index);
        $this->args = array_slice($args, $rest_index);
        $this->theme = \Swidly\Core\Swidly::theme();
    }

    // Add a helper method to check options
    private function hasOption(string $option): bool {
        // Check both short and long options
        return isset($this->opts[$option]) || 
               isset($this->opts[match($option) {
                   'o' => 'option1',
                   'm' => 'option2',
                   'c' => 'option3',
                   default => $option
               }]);
    }

    public function run(): void {
        try {
            $command = array_shift($this->args) ?? '';
            if (empty($command)) {
                throw new \InvalidArgumentException("No command specified");
            }

            if (strpos($command, ':') === false) {
                throw new \InvalidArgumentException("Invalid command format. Use action:method");
            }

            [$action, $method] = explode(':', $command);
            $this->handleCommand($action, $method);
            
            formatPrintLn(['green', 'bold'], "Command executed successfully!");
        } catch (\Exception $e) {
            formatPrintLn(['red', 'bold'], "Error: " . $e->getMessage());
            exit(1);
        }
    }

    private function handleCommand(string $action, string $method): void {
        if (!in_array($action, ['route', 'migration', 'migrate', 'make', 'list'])) {
            throw new \InvalidArgumentException("Unknown action: $action");
        }

        // Use factory to create command
        $command = CommandFactory::create($method, [
            'name' => array_shift($this->args) ?? '',
            'theme' => $this->theme,
            'options' => $this->opts,
            'args' => $this->args
        ]);
        
        $command->execute();
    }
    
    private function createRoute(): void {
        if (!isset($this->args[1], $this->args[2])) {
            throw new \InvalidArgumentException("Route name and path are required");
        }

        $name = $this->args[1];
        $path = $this->args[2];
        $param = $this->buildRouteParameter();
        
        $routeContent = sprintf(self::ROUTE_TEMPLATE, $path, $param, ucfirst($name), $this->theme['name']);
        $routePath = $this->getRoutePath();

        formatPrintLn(['cyan', 'bold'], "Creating route...");
        file_put_contents($routePath, $routeContent, FILE_APPEND);
    }

    private function buildRouteParameter(): string {
        $param = $this->opts['p'] ?? '';
        if (empty($param)) {
            return '';
        }

        $optional = isset($this->opts['o']) ? '?' : '';
        return $this->args[2][strlen($this->args[2]) - 1] === '/' 
            ? "$optional{$param}" 
            : "/$optional{{$param}}";
    }

    private function getRoutePath(): string {
        $routePath = './routes.php';
        
        while (!file_exists($routePath)) {
            formatPrintLn(['magenta', 'bold'], "Route file not found. Please enter the correct path:");
            $handle = fopen("php://stdin", "r");
            $input = trim(fgets($handle));
            fclose($handle);

            if (!empty($input)) {
                $routePath = $input;
            }
        }

        return $routePath;
    }

    private function runMigration(): void {
        $migrationFiles = array_diff(scandir(ROOTDIR.'/../Migrations/'), ['.', '..']);
        $migrationFilenames = array_map(fn($file) => pathinfo($file)['filename'], $migrationFiles);
        
        formatPrintLn(['cyan', 'bold'], "Running migrations...");
        foreach (handle_migrate($migrationFilenames) as $migration) {
            $instance = new $migration->name();
            $instance->up();
        }
    }

    private function makeMigration(): void {
        $migrationStr = sprintf(self::MIGRATION_TEMPLATE, date('mdYhi'), '{up}', '{down}');
        $migrationPath = ROOTDIR.'/../Migrations/Version'.date('mdYhi').'.php';

        formatPrintLn(['cyan', 'bold'], "Creating migration files...");
        file_put_contents($migrationPath, $migrationStr);
    }
}

function handle_migrate(array $migrationFilenames) {
    foreach ($migrationFilenames as $migrationName) {
        $className = 'Swidly\Migrations\\'.$migrationName;
        $instance = new $className();
        yield new \ReflectionClass(get_class($instance));
    }
}

function makeMigration() {
$migrationStr = <<<STR
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
        formatPrintLn(['cyan', 'bold'], "Creating migration files..");
        $date = date('mdYhi');
        $entities = getEntities();
        $addUpSqls = [];
        $addDownSqls = [];

        foreach ($entities as $entity) {
            $tableInstance = $entity->getAttributes(Swidly\Core\Attributes\Table::class)[0]->newInstance();
            $tableName = $tableInstance->name;
            $upSql = '';
            $props = $entity->getProperties();

            $upSql.= 'CREATE TABLE IF NOT EXISTS '.$tableName.' (';

            foreach ($props as $prop) {
                $attributes = $prop->getAttributes(Swidly\Core\Attributes\Column::class);

                foreach ($attributes as $attribute) {
                    $name = $prop->getName() ?? '';
                    $instance = $attribute->newInstance();
                    $type = $instance->type;
                    $length = $instance->length ?? null;
                    $unique = $instance->unique ?? false;
                    $nullable = $instance->nullable ?? false;
                    $isPrimary = $instance->isPrimary ?? false;
                    $default = $instance->default ?? 'NULL';

                    $dataType = @$type->value;

                    if ($dataType === 'varchar' && !isset($length)) {
                        $length = 255;
                    }

                    $lengthAttr = isset($length) ? '('. $length .')' : '';
                    $autoIncrement = $isPrimary ? ' AUTO_INCREMENT' : '';
                    $primary = $isPrimary ? ' PRIMARY KEY' : '';
                    $null = $nullable ? ' DEFAULT '.$default : ' NOT NULL';
                    $unsigned = ''; //$dataType === 'int' ? ' UNSIGNED' : '';

                    $upSql.= $name.' '.strtoupper($dataType).$lengthAttr.$unsigned.strtoupper($null).strtoupper($autoIncrement).$primary.', ';
                }
            }
            
            $upSql = rtrim($upSql, ', ');
            $upSql.= ')';

            array_push($addUpSqls, '$this->addSql(\''. $upSql ."');\r\n");
            array_push($addDownSqls, '$this->addSql(\'DROP TABLE IF EXISTS '.$tableName.'\');'."\r\n");
        }
        
        $migration = sprintf($migrationStr, $date);  
        $result = preg_replace('/({up})/', implode(' ', $addUpSqls), $migration);
        $result = preg_replace('/({down})/', implode(' ', $addDownSqls), $result);

        file_put_contents(ROOTDIR.'/../Migrations/Version'.$date.'.php', $result);
}

function getEntities() {
    $entities = [];
    $models = array_diff(scandir(ROOTDIR.'/../models/'), array('.', '..'));
    $modelFilenames = array_map(fn($model) => pathinfo($model)['filename'], $models);

    foreach ($modelFilenames as $model) {
        $class = 'models/'.$model;
        $dir = ROOTDIR.'/../'.$class.'.php';
        if(file_exists($dir)) {
            $className = 'Swidly\Models\\'.$model;
            $instance = new $className();

            array_push($entities, new \ReflectionClass(get_class($instance)));
        } else {
            echo 'file does not exist '.$class;
        }
    }

    return $entities;
}

// Initialize and run console
$console = new Console($_SERVER['argv']);
$console->run();