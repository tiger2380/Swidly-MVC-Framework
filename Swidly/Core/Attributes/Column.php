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
        public mixed $default = null,
        public ?string $mapping = null,
        public ?bool $index = false,
        public ?bool $fulltext = false,
        public ?bool $autoIncrement = false,
        public ?string $comment = null,
        public ?string $after = null,
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

    public function getDefault(): mixed {
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

    public function setPrimary(bool $isPrimary): void {
        $this->isPrimary = $isPrimary;
    }

    public function setDefault(mixed $default): void {
        $this->default = $default;
    }

    public function getMapping(): ?string {
        return $this->mapping;
    }

    public function setMapping(string $mapping): void {
        $this->mapping = $mapping;
    }

    public function hasIndex(): ?bool {
        return $this->index;
    }

    public function setIndex(bool $index): void {
        $this->index = $index;
    }

    public function isFulltext(): ?bool {
        return $this->fulltext;
    }

    public function setFulltext(bool $fulltext): void {
        $this->fulltext = $fulltext;
    }

    public function isAutoIncrement(): ?bool {
        return $this->autoIncrement;
    }

    public function setAutoIncrement(bool $autoIncrement): void {
        $this->autoIncrement = $autoIncrement;
    }

    public function getComment(): ?string {
        return $this->comment;
    }

    public function setComment(?string $comment): void {
        $this->comment = $comment;
    }

    public function getAfter(): ?string {
        return $this->after;
    }

    public function setAfter(?string $after): void {
        $this->after = $after;
    }
}