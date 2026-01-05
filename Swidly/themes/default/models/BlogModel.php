<?php
namespace Swidly\themes\default\models;
use Swidly\Core\Attributes\Column;
use Swidly\Core\Attributes\Table;
use Swidly\Core\Enums\Types;
use Swidly\Core\Model;

#[Table(name: 'blog')]
class BlogModel extends Model {
    #[Column(type: Types::INTEGER, isPrimary: true)]
    public int $id;

    #[Column(type: Types::STRING, length: 255)]
    public string $title;

    #[Column(type: Types::TEXT)]
    public string $content;

    #[Column(type: Types::DATETIME)]
    public string $createdAt;

    #[Column(type: Types::DATETIME)]
    public string $updatedAt;

    #[Column(type: Types::INTEGER)]
    public int $userId;

    #[Column(type: Types::INTEGER)]
    public int $categoryId;

    #[Column(type: Types::INTEGER)]
    public int $status;

    #[Column(type: Types::INTEGER)]
    public int $views;

    #[Column(type: Types::INTEGER)]
    public int $likes;

    #[Column(type: Types::INTEGER)]
    public int $dislikes;

    #[Column(type: Types::STRING)]
    public ?string $comments;

    #[Column(type: Types::INTEGER)]
    public int $shares;

    #[Column(type: Types::STRING, length: 255)]
    public string $slug;
}