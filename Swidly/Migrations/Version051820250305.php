<?php

    declare(strict_types=1);

    namespace Swidly\Migrations;

    use Swidly\Core\AbstractMigration;

    final class Version051820250305 extends AbstractMigration
    {
        public function getDescription(): string
        {
            return 'Insert initial blog posts';
        }

        public function up(): void
        {
            $this->addSql("INSERT INTO `blog` (`title`, `slug`, `body`, `createdAt`) VALUES
            ('First Post', 'first-post', 'This is the content of the first post.', NOW()),
            ('Second Post', 'second-post', 'This is the content of the second post.', NOW()),
            ('Third Post', 'third-post', 'This is the content of the third post.', NOW())");
        }

        public function down(): void
        {
            $this->addSql("DELETE FROM `blog` WHERE `id` IN (1, 2, 3)");
        }
    }