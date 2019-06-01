<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190601110936 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'DELETE FROM files WHERE NOT EXISTS'
            . '(SELECT * FROM package WHERE files.id = package.files_id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->warnIf(true, 'Removal of orphaned files cannot be reverted');
    }
}
