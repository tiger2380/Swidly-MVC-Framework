<?php

namespace Swidly\Core\Commands;

use Dom\Comment;
use Swidly\Core\Router;
use Swidly\Core\Factory\CommandFactory;

class ListCommand extends AbstractCommand 
{
    public function execute(): void 
    {
        $name = $this->options['name'] ?? '';
        $theme = $this->options['theme'] ?? [];
        $options = $this->options['options'] ?? [];
        $args = $this->options['args'] ?? [];
        $filename = $args[0] ?? '';

        match ($name) {
            'routes' => $this->listRoutes(),
            default => throw new \InvalidArgumentException("Unknown command: $name"),
        };
    }

    private function listRoutes(): void 
    {
        $routes = Router::getRoutes();

        if (empty($routes)) {
            formatPrintLn(['red'], "No routes available.");
            return;
        }

        formatPrintLn(['green', 'bold'], "Available Routes:");
        foreach ($routes as $routeType => $route) {
            formatPrintLn(['yellow'], "Type: $routeType");
            foreach ($route as $path => $details) {
                formatPrintLn(['cyan'], "Path: $path");
            }
            formatPrintLn(['green'], "-------------------------");
        }
    }
    public function getOptions(): array 
    {
        return $this->options;
    }
    public function setOptions(array $options): void 
    {
        $this->options = $options;
    }
    public function __construct(array $options = []) 
    {
        parent::__construct($options);
    }
    public function getCommandName(): string 
    {
        return 'list';
    }
    public function getCommandDescription(): string 
    {
        return 'List all available commands in the Swidly framework';
    }
}