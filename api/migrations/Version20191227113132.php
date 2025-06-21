<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191227113132 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE repository ADD sha256sum VARCHAR(64) DEFAULT NULL, DROP mTime');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE repository ADD mTime DATETIME DEFAULT NULL, DROP sha256sum');
    }

    #[\Override]
    public function isTransactional(): bool
    {
        return false;
    }
}
