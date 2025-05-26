<?php

declare(strict_types=1);

namespace Swidly\Core\Attributes;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class Route {
    public function __construct(
        public array|string $methods, 
        public array|string $path, 
        public ?string $name = null,
        public ?string $action = null,
    ){}
}