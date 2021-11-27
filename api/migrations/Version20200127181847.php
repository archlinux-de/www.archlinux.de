<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200127181847 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package DROP md5sum');
        $this->addSql('ALTER TABLE releng_release DROP md5_sum');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE package ADD md5sum VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`'
        );
        $this->addSql(
            'ALTER TABLE releng_release ADD md5_sum VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`'
        );
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
