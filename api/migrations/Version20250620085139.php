<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250620085139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE package
            ADD metadata_name VARCHAR(255) DEFAULT NULL,
            ADD metadata_type VARCHAR(255) DEFAULT NULL,
            ADD metadata_german_description VARCHAR(255) DEFAULT NULL,
            ADD metadata_categories VARCHAR(255) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE package
            DROP metadata_name,
            DROP metadata_type,
            DROP metadata_german_description,
            DROP metadata_categories'
        );
    }
}
