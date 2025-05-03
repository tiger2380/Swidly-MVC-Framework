<?php

namespace Swidly\Core\Commands;

use Dom\Comment;
use Swidly\Core\Factory\CommandFactory;

class ControllerCommand extends AbstractCommand 
{
     private const CONTROLLER_TEMPLATE = <<<'STR'
<?php
namespace Swidly\themes\%s\controllers;

use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Factory\CommandFactory;

use Swidly\Core\Controller;
use Swidly\Core\Attributes\Route;
use Swidly\Core\Swidly;
use Swidly\Core\SwidlyException;
use Swidly\Middleware\CsrfMiddleware;

/**
 * @throws SwidlyException
 */

class %sController extends Controller {
    #[Route(methods: ['GET'], path: '/%s')]
    public function Index($req, $res) {
        echo 'This is %s controller.';
    }
}
STR;

    public function execute(): void 
    {
        $name = $this->options['name'] ?? '';
        $theme = $this->options['theme'] ?? [];
        $options = $this->options['options'] ?? [];
        
        if (empty($name)) {
            throw new \InvalidArgumentException("Controller name is required");
        }

        $controllerPath = sprintf(
            $theme['base'].'/controllers/%sController.php', 
            ucfirst($name)
        );
        
         if (file_exists($controllerPath)) {
            throw new \RuntimeException("Controller already exists: $controllerPath");
        }

        formatPrintLn(['cyan', 'bold'], "Creating controller...");
        $content = sprintf(self::CONTROLLER_TEMPLATE, $theme['name'], ucfirst($name), strtolower($name), ucfirst($name));
        file_put_contents($controllerPath, $content);

       if (isset($options['m'])) {
          $model = CommandFactory::create('model', [
              'name' => $name,
              'theme' => $theme,
          ]);
          $model->execute();
       }
    }
}