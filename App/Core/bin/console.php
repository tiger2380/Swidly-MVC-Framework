#! /usr/bin/php
<?php

require_once 'formatPrint.php';
define('BASENAME', dirname(dirname(__FILE__)));

require BASENAME.'/../../bootstrap.php';
$routeStr = <<<STR

\$this->get('%s%s', '%sController::Index')
->name('%s');
STR;

$args = $_SERVER['argv'];

$opts = getopt("p::v::o::", [], $rest_index);
$args = array_slice($args, $rest_index);
$command = $args[0];

if(stripos($command, ':') > -1) {
    list($action, $method) = explode(':', $command);

    if($action === 'make') {
        if($method === 'controller') {
            $name = $args[1];
            createController($name);
        } else if ($method === 'route') {
            $name = $args[1];
            $path = $args[2];

            $param = $opts['p'] ?? '';
            if(!empty($param)) {
                $opt = $opts['o'] ? '?' : '';
                if($path[strlen($path) - 1] === '/') {
                    $param = "$opt{$param}";    
                } else {
                    $param = "/$opt{{$param}}";
                }
            }
            $routeFormat = sprintf($routeStr, $path, $param, ucfirst($name), $name);
            $routeFile = './routes.php';
    
            if(!file_exists($routeFile)) {
                ask:
                formatPrintLn(['magenta', 'bold'], "It seems like I can not find the route file. Type if the path below:");
                $handle = fopen ("php://stdin","r");
                $line = fgets($handle);
                if(trim($line) != ''){
                    $routeFile = trim($line);
    
                    if(!file_exists($routeFile)) {
                        goto ask;
                    }
                }
                fclose($handle);
            }
    
            createController($name);
            formatPrintLn(['cyan', 'bold'], "Creating route..");
            file_put_contents($routeFile, $routeFormat, FILE_APPEND);
        } else if ($method === 'migration') {
            makeMigration();
        } else if ($method === 'migrate') {
            
        } else {
            formatPrintLn(['red', 'bold'], "Unknown command");
            exit;
        }
    } 
}

formatPrintLn(['yellow', 'bold'], "Finished!!");

function createController(string $name) {
$controllerStr = <<<STR
<?php
    namespace App\Controllers;

    class %sController extends \App\Controller {
        function Index(\$req, \$res) {
            echo 'This is %s controller.';
        }
    }
STR;

    formatPrintLn(['cyan', 'bold'], "Creating controller..");
    $controllerFormat = sprintf($controllerStr, ucfirst($name), ucfirst($name));
    $newFileName = './App/Controllers/'.ucfirst($name).'Controller.php';
    if(!file_exists($newFileName)) {
        file_put_contents($newFileName, $controllerFormat);
    } else {
        formatPrintLn(['red', 'bold'], "Controller already exists..");
    }
}

function makeMigration() {
$migrationStr = <<<STR
    <?php

        declare(strict_types=1);

        namespace App\Migration;

        use App\Core\AbstractMigration;

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

        $date = date('mdYhi');
        $entities = getEntities();
        $addUpSqls = [];
        $addDownSqls = [];

        foreach ($entities as $entity) {
            $tableInstance = $entity->getAttributes(App\Core\Attributes\Table::class)[0]->newInstance();
            $tableName = $tableInstance->name;
            $upSql = '';
            $props = $entity->getProperties();

            $upSql.= 'CREATE TABLE '.$tableName.'(';

            foreach ($props as $prop) {
                $attributes = $prop->getAttributes(App\Core\Attributes\Column::class);

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

                    if ($isPrimary && !isset($length)) {
                        $dataType = 'int';
                        $length = 6;
                    }

                    $lengthAttr = isset($length) ? '('. $length .')' : '';
                    $autoIncrement = $isPrimary ? ' AUTO_INCREMENT' : '';
                    $primary = $isPrimary ? ' PRIMARY KEY' : '';
                    $null = $nullable ? ' DEFAULT '.$default : ' NOT NULL';
                    $unsigned = $dataType === 'int' ? ' UNSIGNED' : '';

                    $upSql.= $name.' '.$dataType.$lengthAttr.$unsigned.$autoIncrement.$primary.$null.', ';
                }
            }
            
            $upSql = rtrim($upSql, ', ');
            $upSql.= ')';

            array_push($addUpSqls, '$this->addSql(\''. $upSql ."');\r\n");
            array_push($addDownSqls, '$this->addSql(\'DROP TABLE '.$tableName.'\');'."\r\n");
        }
        
        $migration = sprintf($migrationStr, $date);            
        $result = preg_replace('/({up})/', implode(' ', $addUpSqls), $migration);
        $result = preg_replace('/({down})/', implode(' ', $addDownSqls), $result);

        file_put_contents(BASENAME.'/../migrations/Version'.$date.'.php', $result);
}

function getEntities() {
    $entities = [];
    $models = array_diff(scandir(BASENAME.'/../Models/'), array('.', '..'));
    $modelFilenames = array_map(fn($model) => pathinfo($model)['filename'], $models);

    foreach ($modelFilenames as $model) {
        $class = 'Models/'.$model;
        $dir = BASENAME.'/../'.$class.'.php';
        if(file_exists($dir)) {
            $className = 'App\Models\\'.$model;
            $instance = new $className();

            array_push($entities, new \ReflectionClass(get_class($instance)));
        } else {
            echo 'file does not exist '.$class;
        }
    }

    return $entities;
}