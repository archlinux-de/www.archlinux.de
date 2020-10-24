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
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE mirror DROP isos');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE mirror ADD isos TINYINT(1) NOT NULL');
    }
}
