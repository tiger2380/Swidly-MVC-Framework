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

    public function getType(): ?Types {
        return $this->type;
    }

    public function getLength(): ?int {
        return $this->length;
    }

    public function isUnique(): ?bool {
        return $this->unique;
    }

    public function isNullable(): ?bool {
        return $this->nullable;
    }

    public function isPrimary(): ?bool {
        return $this->isPrimary;
    }

    public function getDefault(): ?string {
        return $this->default;
    }

    public function setType(Types $type): void {
        $this->type = $type;
    }

    public function setLength(int $length): void {
        $this->length = $length;
    }

    public function setUnique(bool $unique): void {
        $this->unique = $unique;
    }

    /**
     * @param bool $nullable
     * @return void
     * @throws \Exception
     */
    public function setNullable(bool $nullable): void {
        $this->nullable = $nullable;
    }
}