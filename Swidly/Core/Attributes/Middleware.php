<?php

declare(strict_types=1);

namespace Swidly\Core\Attributes;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class Middleware {
    function __construct(
        public string $callback
    ) {}
}