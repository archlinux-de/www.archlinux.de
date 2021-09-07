<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210907214927 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql(
            'ALTER TABLE package ADD popularity_popularity DOUBLE PRECISION DEFAULT NULL, ADD popularity_samples INT DEFAULT NULL, ADD popularity_count INT DEFAULT NULL, ADD popularity_start_month INT DEFAULT NULL, ADD popularity_end_month INT DEFAULT NULL, DROP popularity'
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql(
            'ALTER TABLE package ADD popularity DOUBLE PRECISION DEFAULT \'0\' NOT NULL, DROP popularity_popularity, DROP popularity_samples, DROP popularity_count, DROP popularity_start_month, DROP popularity_end_month'
        );
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
