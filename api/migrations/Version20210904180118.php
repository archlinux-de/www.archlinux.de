<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210904180118 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        // phpcs:disable
        $this->addSql(
            'ALTER TABLE package RENAME COLUMN compressedSize TO compressed_size, RENAME COLUMN installedSize TO installed_size, RENAME COLUMN filename TO file_name, RENAME COLUMN builddate TO build_date'
        );
        $this->addSql('DROP INDEX idx_de686795566e72ba ON package');
        $this->addSql('CREATE INDEX IDX_DE686795ADE25A90 ON package (build_date)');
        // phpcs:enable
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        // phpcs:disable
        $this->addSql('DROP INDEX idx_de686795ade25a90 ON package');
        $this->addSql('CREATE INDEX IDX_DE686795566E72BA ON package (build_date)');
        $this->addSql(
            'ALTER TABLE package RENAME COLUMN compressed_size TO compressedSize, RENAME COLUMN installed_size TO installedSize, RENAME COLUMN file_name TO filename, RENAME COLUMN build_date TO builddate'
        );
        // phpcs:enable
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
