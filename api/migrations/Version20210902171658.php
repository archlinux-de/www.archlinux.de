<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210902171658 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE releng_release DROP iso_url');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE releng_release ADD iso_url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`'
        );
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
