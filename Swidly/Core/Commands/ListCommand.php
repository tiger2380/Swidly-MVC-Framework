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
            'components' => $this->listComponents(),
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

    private function listComponents(): void 
    {
        $theme = $this->options['theme'] ?? [];
        $themeName = $theme['name'] ?? 'localgem';
        $themesDir = dirname(__DIR__, 2) . '/themes';
        
        formatPrintLn(['green', 'bold'], "Available Components:");
        formatPrintLn(['white'], "");

        // Get list of themes
        $themes = [];
        if (is_dir($themesDir)) {
            $themes = array_filter(scandir($themesDir), function($item) use ($themesDir) {
                return $item !== '.' && $item !== '..' && is_dir($themesDir . '/' . $item);
            });
        }

        if (empty($themes)) {
            formatPrintLn(['red'], "No themes found.");
            return;
        }

        $totalComponents = 0;
        foreach ($themes as $themeDir) {
            $componentsDir = $themesDir . '/' . $themeDir . '/components';
            
            if (!is_dir($componentsDir)) {
                continue;
            }

            $components = array_filter(scandir($componentsDir), function($item) use ($componentsDir) {
                return $item !== '.' && $item !== '..' && pathinfo($item, PATHINFO_EXTENSION) === 'php';
            });

            if (!empty($components)) {
                formatPrintLn(['yellow', 'bold'], "Theme: $themeDir");
                foreach ($components as $component) {
                    $componentName = pathinfo($component, PATHINFO_FILENAME);
                    // Convert PascalCase to kebab-case for usage hint
                    $alias = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $componentName));
                    
                    formatPrintLn(['cyan'], "  - $componentName");
                    formatPrintLn(['white'], "    Usage: <x-{$alias}>content</x-{$alias}>");
                    $totalComponents++;
                }
                formatPrintLn(['white'], "");
            }
        }

        if ($totalComponents === 0) {
            formatPrintLn(['red'], "No components found.");
        } else {
            formatPrintLn(['green', 'bold'], "Total components: $totalComponents");
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