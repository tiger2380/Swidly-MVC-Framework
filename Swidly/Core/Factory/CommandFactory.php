<?php

namespace Swidly\Core\Factory;

use Swidly\Core\Commands\ListCommand;
use Swidly\Core\Commands\MakeCommand;
use Swidly\Core\Commands\ModelCommand;
use Swidly\Core\Commands\MigrationCommand;
use Swidly\Core\Commands\ControllerCommand;
use Swidly\Core\Commands\ComponentCommand;
use Swidly\Core\Commands\ThemeCommand;
use Swidly\Core\Commands\AdminCommand;
use Swidly\Core\Commands\BookingCommand;

class CommandFactory
{
    public static function create(string $type, array $options = []) : mixed
    {
        return match($type) {
            'list' => new ListCommand($options),
            'make' => new MakeCommand($options),
            'model' => new ModelCommand($options),
            'controller' => new ControllerCommand($options),
            'migration' => new MigrationCommand($options),
            'component' => new ComponentCommand($options),
            'theme' => new ThemeCommand($options),
            'admin' => new AdminCommand($options),
            'booking' => new BookingCommand($options),
            default => throw new \InvalidArgumentException("Unknown command type: $type")
        };
    }
}