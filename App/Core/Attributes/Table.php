<?php

declare(strict_types=1);

namespace App\Core\Attributes;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Table {
    function __construct(
        public ?string $name = null
    ) {}
}