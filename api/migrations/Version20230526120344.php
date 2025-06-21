<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230526120344 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror DROP protocol');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror ADD protocol VARCHAR(255) NOT NULL');
    }

    #[\Override]
    public function isTransactional(): bool
    {
        return false;
    }
}
