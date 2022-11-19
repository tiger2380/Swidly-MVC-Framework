<?php

declare(strict_types=1);

namespace App\Core\Attributes;

#[\Attribute]
class Column {
    function __construct(
        public ?string $type = null,
        public ?int $length = null,
        public ?bool $unique = false,
        public ?bool $nullable = false,
        public ?bool $isPrimary = false,
        public ?string $default = null
    ){}
}