<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220122093829 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE package CHANGE repository_id repository_id INT NOT NULL, CHANGE files_id files_id INT NOT NULL'
        );
        $this->addSql(
            'ALTER TABLE packages_relation CHANGE source_id source_id INT NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE package CHANGE repository_id repository_id INT DEFAULT NULL, CHANGE files_id files_id INT DEFAULT NULL'
        );
        $this->addSql(
            'ALTER TABLE packages_relation CHANGE source_id source_id INT DEFAULT NULL'
        );
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
