<?php

declare(strict_types=1);

namespace Swidly\Core\Attributes;

use Swidly\Core\Enums\Types;

#[\Attribute]
class Column {
    function __construct(
        public ?Types $type = null,
        public ?int $length = null,
        public ?bool $unique = false,
        public ?bool $nullable = false,
        public ?bool $isPrimary = false,
        public ?string $default = null
    ){}
}