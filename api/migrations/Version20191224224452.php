<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191224224452 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP INDEX UNIQ_CAC6D395989D9B62 ON news_item');
        $this->addSql('ALTER TABLE news_item DROP slug, CHANGE id id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CAC6D39536AC99F1 ON news_item (link)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP INDEX UNIQ_CAC6D39536AC99F1 ON news_item');
        $this->addSql(
            'ALTER TABLE news_item ADD slug VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,'
            . ' CHANGE id id VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`'
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CAC6D395989D9B62 ON news_item (slug)');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
