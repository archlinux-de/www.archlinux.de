<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200531074201 extends AbstractMigration
{
    public function preUp(Schema $schema): void
    {
        $this->write('Removing incomplete mirrors');
        $this->connection->delete('mirror', ['last_sync' => null]);
        $this->connection->delete('mirror', ['delay' => null]);
        $this->connection->delete('mirror', ['duration_avg' => null]);
        $this->connection->delete('mirror', ['score' => null]);
        $this->connection->delete('mirror', ['completion_pct' => null]);
        $this->connection->delete('mirror', ['duration_stddev' => null]);
        $this->connection->delete('mirror', ['isos' => null]);
        $this->connection->delete('mirror', ['ipv4' => null]);
        $this->connection->delete('mirror', ['ipv6' => null]);
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        // phpcs:disable
        $this->addSql(
            'ALTER TABLE mirror CHANGE last_sync last_sync DATETIME NOT NULL, CHANGE delay delay INT NOT NULL, CHANGE duration_avg duration_avg DOUBLE PRECISION NOT NULL, CHANGE score score DOUBLE PRECISION NOT NULL, CHANGE completion_pct completion_pct DOUBLE PRECISION NOT NULL, CHANGE duration_stddev duration_stddev DOUBLE PRECISION NOT NULL, CHANGE isos isos TINYINT(1) NOT NULL, CHANGE ipv4 ipv4 TINYINT(1) NOT NULL, CHANGE ipv6 ipv6 TINYINT(1) NOT NULL'
        );
        // phpcs:enable
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        // phpcs:disable
        $this->addSql(
            'ALTER TABLE mirror CHANGE last_sync last_sync DATETIME DEFAULT NULL, CHANGE delay delay INT DEFAULT NULL, CHANGE duration_avg duration_avg DOUBLE PRECISION DEFAULT NULL, CHANGE score score DOUBLE PRECISION DEFAULT NULL, CHANGE completion_pct completion_pct DOUBLE PRECISION DEFAULT NULL, CHANGE duration_stddev duration_stddev DOUBLE PRECISION DEFAULT NULL, CHANGE isos isos TINYINT(1) DEFAULT NULL, CHANGE ipv4 ipv4 TINYINT(1) DEFAULT NULL, CHANGE ipv6 ipv6 TINYINT(1) DEFAULT NULL'
        );
        // phpcs:enable
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
