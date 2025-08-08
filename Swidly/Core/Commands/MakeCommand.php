<?php

namespace Swidly\Core\Commands;

use Dom\Comment;
use Swidly\Core\Factory\CommandFactory;

class MakeCommand extends AbstractCommand 
{
    public function execute(): void 
    {
        $action = $this->options['name'] ?? '';
        $theme = $this->options['theme'] ?? [];
        $name = $this->options['args'][0] ?? '';

        if (empty($action) || empty($name)) {
            throw new \InvalidArgumentException("Action and name are required");
        }

        switch ($action) {
            case 'model':
                $command = CommandFactory::create('model', ['name' => $name, 'theme' => $theme]);
                break;
            case 'controller':
                $command = CommandFactory::create('controller', ['name' => $name, 'theme' => $theme]);
                break;
            case 'migration':
                $command = CommandFactory::create('migration', ['name' => $name, 'theme' => $theme]);
                break;
            default:
                throw new \InvalidArgumentException("Unknown action: $action");
        }

        $command->execute();
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
        return 'make';
    }
    public function getCommandDescription(): string 
    {
        return 'Create a new model, controller, or migration';
    }
    public function getCommandUsage(): string 
    {
        return 'make --name <name> --type <model|controller|migration>';
    }
    public function getCommandOptions(): array 
    {
        return [
            'name' => 'The name of the model, controller, or migration',
            'type' => 'The type of command to execute (model, controller, migration)',
            'theme' => 'The theme to use for the command'
        ];
    }
    public function getCommandArguments(): array 
    {
        return [
            'name' => 'The name of the model, controller, or migration to create',
            'type' => 'The type of command to execute (model, controller, migration)'
        ];
    }
    public function getCommandAliases(): array 
    {
        return [
            'm' => 'make',
            'c' => 'create',
            'g' => 'generate'
        ];
    }
    public function getCommandCategory(): string 
    {
        return 'Development';
    }
    public function getCommandVersion(): string 
    {
        return '1.0.0';
    }
    public function getCommandAuthor(): string 
    {
        return 'Swidly Team';
    }
    public function getCommandLicense(): string 
    {
        return 'MIT';
    }
    public function getCommandDependencies(): array 
    {
        return [
            'Swidly\Core\Factory\CommandFactory',
            'Swidly\Core\Commands\ModelCommand',
            'Swidly\Core\Commands\ControllerCommand',
            'Swidly\Core\Commands\MigrationCommand'
        ];
    }
}