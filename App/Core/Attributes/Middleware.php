<?php

declare(strict_types=1);

namespace App\Core\Attributes;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class Middleware {
    function __construct(
        public string $callback
    ) {}
}