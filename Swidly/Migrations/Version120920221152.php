<?php

declare(strict_types=1);

namespace Swidly\Migrations;

use Swidly\Core\AbstractMigration;

final class Version120920221152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added the user table';
    }

    public function up(): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS blog (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, title VARCHAR(50) NOT NULL, createdAt DATETIME NOT NULL, body VARCHAR(255) DEFAULT NULL)');
 $this->addSql('CREATE TABLE IF NOT EXISTS post (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, title VARCHAR(50) NOT NULL, createdAt DATETIME NOT NULL, body VARCHAR(255) NOT NULL)');
 $this->addSql('CREATE TABLE IF NOT EXISTS users (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL)');

    }

    public function down(): void
    {
        $this->addSql('DROP TABLE IF EXISTS blog');
 $this->addSql('DROP TABLE IF EXISTS post');
 $this->addSql('DROP TABLE IF EXISTS users');

    }
}