<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200531072522 extends AbstractMigration
{
    public function preUp(Schema $schema): void
    {
        $this->write('Removing inactive mirrors');
        $this->connection->delete('mirror', ['active' => null]);
        $this->connection->delete('mirror', ['active' => 0]);
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE mirror DROP active');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE mirror ADD active TINYINT(1) DEFAULT NULL');
    }
}
