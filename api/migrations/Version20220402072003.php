<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220402072003 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE releng_release ADD sha256_sum VARCHAR(64) DEFAULT NULL, ADD b2_sum VARCHAR(128) DEFAULT NULL'
        );
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE releng_release DROP sha256_sum, DROP b2_sum'
        );
    }

    #[\Override]
    public function isTransactional(): bool
    {
        return false;
    }
}
