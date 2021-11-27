<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\NewsItem;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class Version20181230070059 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_item ADD slug VARCHAR(191) NOT NULL AFTER id');
    }

    public function postUp(Schema $schema): void
    {
        if ($this->hasColumn('news_item', 'slug')) {
            $slugger = new AsciiSlugger();
            foreach ($this->connection->fetchAllAssociative('SELECT id, title FROM news_item') as $row) {
                $newsItem = (new NewsItem($row['id']))->setTitle($row['title']);
                $this->connection->update(
                    'news_item',
                    ['slug' => $slugger->slug($newsItem->getTitle())],
                    ['id' => $newsItem->getId()]
                );
            }
        } else {
            $this->warnIf(
                true,
                'Column slug on table news_item was not found. Data migration was skipped!'
            );
        }
    }

    private function hasColumn(string $table, string $columnName): bool
    {
        foreach ($this->connection->createSchemaManager()->listTableColumns($table) as $column) {
            if ($column->getName() == $columnName) {
                return true;
            }
        }
        return false;
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_item DROP slug');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
