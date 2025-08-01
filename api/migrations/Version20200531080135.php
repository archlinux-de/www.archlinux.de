<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200531080135 extends AbstractMigration
{
    public function preUp(Schema $schema): void
    {
        $this->write('Removing mirrors without ISOs');
        $this->connection->delete('mirror', ['isos' => null]);
        $this->connection->delete('mirror', ['isos' => 0]);
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror DROP isos');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror ADD isos TINYINT(1) NOT NULL');
    }

    #[\Override]
    public function isTransactional(): bool
    {
        return false;
    }
}
