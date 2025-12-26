<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191229122108 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE6867955E237E0650C9D4F7 ON package (name, repository_id)');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_DE6867955E237E0650C9D4F7 ON package');
    }

    #[\Override]
    public function isTransactional(): bool
    {
        return false;
    }
}
