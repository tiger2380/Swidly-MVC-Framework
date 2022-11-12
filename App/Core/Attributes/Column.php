<?php

declare(strict_types=1);

namespace App\Core\Attributes;

#[\Attribute]
class Column {
    function __construct(public ?string $type = null)
    {
        
    }
}