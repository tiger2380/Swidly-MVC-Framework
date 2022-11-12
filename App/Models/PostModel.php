<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Column;

class PostModel extends Model {
    protected $table = 'post';
    protected $idField = 'id';

    #[Column()]
    public int $id;

    #[Column()]
    private ?string $title = null;

    #[Column()]
    private string $createdAt;

    #[Column()]
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