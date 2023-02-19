<?php

declare(strict_types=1);

namespace Swidly\Core\Attributes;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
class Controller {
    function __construct(
        public ?string $name = null,
    ) {}
}