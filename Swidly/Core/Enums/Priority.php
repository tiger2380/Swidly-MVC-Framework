<?php

declare(strict_types=1);

namespace Swidly\Core\Enums;

enum Priority: int {
    case LOWEST = 0;
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
    case HIGHEST = 4;
}
