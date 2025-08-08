<?php

namespace Swidly\Core\Factory;

use Swidly\Core\Commands\ListCommand;
use Swidly\Core\Commands\MakeCommand;
use Swidly\Core\Commands\ModelCommand;
use Swidly\Core\Commands\MigrationCommand;
use Swidly\Core\Commands\ControllerCommand;

class CommandFactory
{
    public static function create(string $type, array $options = []) : mixed
    {
        return match($type) {
            'list' => new ListCommand($options),
            'make' => new MakeCommand($options),
            'migration' => new MigrationCommand($options),
            default => throw new \InvalidArgumentException("Unknown command type: $type")
        };
    }
}