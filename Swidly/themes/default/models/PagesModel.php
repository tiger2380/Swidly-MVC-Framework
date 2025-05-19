<?php

namespace Swidly\themes\default\models;

use Swidly\Core\Model;
use Swidly\Core\Attributes\Table;
use Swidly\Core\Attributes\Column;
use Swidly\Core\Enums\Types;

#[Table(name: 'pages')]
class PagesModel extends Model {
    #[Column(type: Types::INTEGER, isPrimary: true)]
    public int $id;

    #[Column(type: Types::STRING, length: 50)]
    public string $name;

    #[Column(type: Types::STRING, length: 50)]
    public string $slug;

    #[Column(type: Types::INTEGER, length: 50)]
    public int $active;

    #[Column(type: Types::STRING)]
    public string $createdAt;

    #[Column(type: Types::INTEGER, mapping: PagesModel::class)]
    public PagesModel $parent;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function setActive(int $active): void
    {
        $this->active = $active;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getParent(): PagesModel
    {
        return $this->parent;
    }

    public function setParent(PagesModel $parent): void
    {
        $this->parent = $parent;
    }
}