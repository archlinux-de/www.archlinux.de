<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200516121823 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package ADD popularity DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package DROP popularity');
    }

    #[\Override]
    public function isTransactional(): bool
    {
        return false;
    }
}
