<?php

declare(strict_types=1);

namespace Swidly\Core\Enums;

enum Types: string {
    case STRING = 'varchar';
    case INTEGER = 'int';
    case DOUBLE = 'double';
    case DATETIME = 'datetime';
    case BIGINT = 'bigint';
    case TEXT = 'text';

    case TIMESTAMP = 'timestamp';
}
