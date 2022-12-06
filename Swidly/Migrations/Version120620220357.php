<?php

declare(strict_types=1);

namespace Swidly\Migrations;

use Swidly\Core\AbstractMigration;

final class Version120620220357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added blog and post sample tables';
    }

    public function up(): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS blog (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, title VARCHAR(50) NOT NULL, createdAt DATETIME NOT NULL, body VARCHAR(255) DEFAULT NULL)');
 $this->addSql('CREATE TABLE IF NOT EXISTS post (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, title VARCHAR(50) NOT NULL, createdAt DATETIME NOT NULL, body VARCHAR(255) NOT NULL)');

    }

    public function down(): void
    {
        $this->addSql('DROP TABLE IF EXISTS blog');
 $this->addSql('DROP TABLE IF EXISTS post');

    }
}