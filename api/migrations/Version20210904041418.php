<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210904041418 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package DROP pgp_signature');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package ADD pgp_signature LONGBLOB DEFAULT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
