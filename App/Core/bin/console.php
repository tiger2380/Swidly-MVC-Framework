#! /usr/bin/php
<?php

require_once './App/bin/formatPrint.php';

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
        } else if ($action === 'migration') {
            createMigration();
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

function createMigration() {
$migrationStr = <<<STR
    <?php

        declare(strict_types=1);

        namespace App\Migration;

        final class Version%s extends AbstractMigration
        {
            public function getDescription(): string
            {
                return '';
            }

            public function up(): void
            {
                \$this->addSql(%s);
            }

            public function down(): void
            {
                \$this->addSql(%s);
            }
        }
STR;

        $filename = date('mdYhia');
        $migration = sprintf($migrationStr, $filename);
}