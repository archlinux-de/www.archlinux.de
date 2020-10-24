<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\NewsItem;
use App\Service\Slugger;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class Version20181230070059 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE news_item ADD slug VARCHAR(191) NOT NULL AFTER id');
    }

    /**
     * @param Schema $schema
     */
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

    /**
     * @param string $table
     * @param string $columnName
     * @return bool
     */
    private function hasColumn(string $table, string $columnName): bool
    {
        foreach ($this->connection->getSchemaManager()->listTableColumns($table) as $column) {
            if ($column->getName() == $columnName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE news_item DROP slug');
    }
}
