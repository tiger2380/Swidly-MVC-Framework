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

    public function getId(): int {
        return $this->id ?? 0;
    }

    public function getTitle(): string {
        return $this->title ?? '';
    }

    public function getContent(): string {
        return $this->content ?? '';
    }

    public function getCreatedAt(): string {
        return $this->createdAt ?? '';
    }

    public function getUpdatedAt(): string {
        return $this->updatedAt ?? '';
    }


    public function getUserId(): int {
        return $this->userId ?? 0;
    }

    public function getCategoryId(): int {
        return $this->categoryId ?? 0;
    }

    public function getStatus(): int {
        return $this->status ?? 0;
    }

    public function getViews(): int {
        return $this->views ?? 0;
    }

    public function getLikes(): int {
        return $this->likes ?? 0;
    }

    public function getDislikes(): int {
        return $this->dislikes ?? 0;
    }

    public function getComments(): ?string {
        return $this->comments ?? '';
    }

    public function setTitle(string $title): void {
        $this->title = $title;
    }

    public function setContent(string $content): void {
        $this->content = $content;
    }

    public function setCreatedAt(string $createdAt): void {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(string $updatedAt): void {
        $this->updatedAt = $updatedAt;
    }

    public function setUserId(int $userId): void {
        $this->userId = $userId;
    }

    public function setCategoryId(int $categoryId): void {
        $this->categoryId = $categoryId;
    }

    public function setStatus(int $status): void {
        $this->status = $status;
    }

    public function setViews(int $views): void {
        $this->views = $views;
    }

    public function setLikes(int $likes): void {
        $this->likes = $likes;
    }

    public function setDislikes(int $dislikes): void {
        $this->dislikes = $dislikes;
    }

    public function setComments(?string $comments): void {
        $this->comments = $comments;
    }

    public function setShares(int $shares): void {
        $this->shares = $shares;
    }

    public function getShares(): int {
        return $this->shares ?? 0;
    }

    public function getAll(): array {
        return $this->get();
    }

    public function getById(int $id): ?BlogModel {
        return $this->find($id);
    }

    public function create(array $data): bool {
        return $this->insert($data);
    }

    public function getSlug(): string {
        return $this->slug ?? '';
    }

    public function setSlug(string $slug): void {
        $this->slug = $slug;
    }
}