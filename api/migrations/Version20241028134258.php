<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241028134258 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package CHANGE groups groups LONGTEXT DEFAULT NULL, CHANGE licenses licenses LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package CHANGE groups groups LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE licenses licenses LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
