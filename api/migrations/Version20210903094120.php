<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210903094120 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE releng_release RENAME COLUMN torrent_file_name TO file_name, RENAME COLUMN torrent_magnet_uri TO magnet_uri, RENAME COLUMN torrent_file_length TO file_length'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE releng_release RENAME COLUMN file_name TO torrent_file_name, RENAME COLUMN magnet_uri TO torrent_magnet_uri, RENAME COLUMN file_length TO torrent_file_length'
        );
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
