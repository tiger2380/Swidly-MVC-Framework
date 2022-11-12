<?php

declare(strict_types=1);

namespace App\Core\Attributes;

#[\Attribute]
class Route {
    public function __construct(public string $method, public string $path, public ?string $name = null)
    {
        
    }
}