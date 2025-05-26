<?php

namespace Swidly\Core\Factory;

use Swidly\Core\Commands\ModelCommand;
use Swidly\Core\Commands\MigrationCommand;
use Swidly\Core\Commands\ControllerCommand;

class CommandFactory
{
    public static function create(string $type, array $options = []) : mixed
    {
        return match($type) {
            'controller' => new ControllerCommand($options),
            'model' => new ModelCommand($options),
            'migration' => new MigrationCommand($options),
            default => throw new \InvalidArgumentException("Unknown command type: $type")
        };
    }
}