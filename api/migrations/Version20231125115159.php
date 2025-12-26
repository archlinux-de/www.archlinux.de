<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231125115159 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package DROP popularity_start_month, DROP popularity_end_month');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE package ADD popularity_start_month INT DEFAULT NULL, ADD popularity_end_month INT DEFAULT NULL');
    }

    #[\Override]
    public function isTransactional(): bool
    {
        return false;
    }
}
