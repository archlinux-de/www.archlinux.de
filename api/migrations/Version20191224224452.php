<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191224224452 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CAC6D395989D9B62 ON news_item');
        $this->addSql('ALTER TABLE news_item DROP slug, CHANGE id id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CAC6D39536AC99F1 ON news_item (link)');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CAC6D39536AC99F1 ON news_item');
        $this->addSql(
            'ALTER TABLE news_item ADD slug VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,'
            . ' CHANGE id id VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`'
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CAC6D395989D9B62 ON news_item (slug)');
    }

    #[\Override]
    public function isTransactional(): bool
    {
        return false;
    }
}
