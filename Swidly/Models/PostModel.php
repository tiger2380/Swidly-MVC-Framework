<?php

namespace Swidly\Models;

use Swidly\Core\Model;
use Swidly\Core\Attributes\Table;
use Swidly\Core\Attributes\Column;
use Swidly\Core\Enums\Types;

#[Table(name: 'post')]
class PostModel extends Model {
    #[Column(isPrimary: true, type: Types::INTEGER)]
    public int $id;

    #[Column(type: Types::STRING, length: 50)]
    private ?string $title = null;

    #[Column(type: Types::DATETIME)]
    private string $createdAt;

    #[Column(type: Types::STRING)]
    private string $body;

    public function getId(): int {
        return $this->id ?? 0;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setTitle(string $title): self {
        $this->title = $title;

        return $this;
    }

    public function setCreatedAt(string $createdAt): self {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): string {
        return $this->createdAt;
    }

    public function setBody(string $body): self {
        $this->body = $body;

        return $this;
    }

    public function getBody(): string {
        return $this->body;
    }
}