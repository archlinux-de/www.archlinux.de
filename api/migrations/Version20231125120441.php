<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231125120441 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror ADD popularity_popularity DOUBLE PRECISION DEFAULT NULL, ADD popularity_samples INT DEFAULT NULL, ADD popularity_count INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror DROP popularity_popularity, DROP popularity_samples, DROP popularity_count');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
