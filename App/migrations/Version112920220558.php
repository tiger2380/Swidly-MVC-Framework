    <?php

        declare(strict_types=1);

        namespace App\Migration;

        use App\Core\AbstractMigration;

        final class Version112920220558 extends AbstractMigration
        {
            public function getDescription(): string
            {
                return '';
            }

            public function up(): void
            {
                $this->addSql('CREATE TABLE blog(id int(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL, title varchar(50) NOT NULL, createdAt datetime NOT NULL, body varchar DEFAULT NULL)');
 $this->addSql('CREATE TABLE post(id int(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL, title varchar(50) NOT NULL, createdAt datetime NOT NULL, body varchar NOT NULL)');

            }

            public function down(): void
            {
                $this->addSql('DROP TABLE blog');0
 $this->addSql('DROP TABLE post');

            }
        }