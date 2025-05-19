<?php

    declare(strict_types=1);

    namespace Swidly\Migrations;

    use Swidly\Core\AbstractMigration;

    final class Version051820250607 extends AbstractMigration
    {
        public function getDescription(): string
        {
            return '';
        }

        public function up(): void
        {
            $this->addSql('CREATE TABLE IF NOT EXISTS pages (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR (50) NOT NULL, slug VARCHAR (50) NOT NULL, active INT (50) NOT NULL, createdAt VARCHAR (255) NOT NULL, parent INT NOT NULL)');

        }

        public function down(): void
        {
            $this->addSql('DROP TABLE IF EXISTS pages');

        }
    }