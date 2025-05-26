<?php

declare(strict_types=1);

namespace Swidly\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteGroup
{
    public function __construct(
        public readonly string $prefix = '',
        public readonly array $options = []
    ) {}
}