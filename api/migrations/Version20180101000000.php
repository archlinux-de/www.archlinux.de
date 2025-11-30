<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Name\OptionallyQualifiedName;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema before migrations were introduced
 */
final class Version20180101000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $knownTables = $this->connection->createSchemaManager()->introspectTableNames();

        if (count($knownTables) === 1 && $knownTables[0]->toString() === '"doctrine_migration_versions"') {
            $this->addSql('CREATE TABLE country (code VARCHAR(2) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(code)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE mirror (url VARCHAR(191) NOT NULL, country_id VARCHAR(2) DEFAULT NULL, protocol VARCHAR(255) NOT NULL, last_sync DATETIME DEFAULT NULL, delay INT DEFAULT NULL, duration_avg DOUBLE PRECISION DEFAULT NULL, score DOUBLE PRECISION DEFAULT NULL, completion_pct DOUBLE PRECISION DEFAULT NULL, duration_stddev DOUBLE PRECISION DEFAULT NULL, isos TINYINT(1) DEFAULT NULL, ipv4 TINYINT(1) DEFAULT NULL, ipv6 TINYINT(1) DEFAULT NULL, active TINYINT(1) DEFAULT NULL, INDEX IDX_5BA71B4AF92F3E70 (country_id), INDEX IDX_5BA71B4ABA003126 (last_sync), PRIMARY KEY(url)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE news_item (id VARCHAR(191) NOT NULL, title VARCHAR(255) NOT NULL, link VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, last_modified DATETIME NOT NULL, author_name VARCHAR(255) NOT NULL, author_uri VARCHAR(255) DEFAULT NULL, INDEX IDX_CAC6D395270A2932 (last_modified), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE repository (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, testing TINYINT(1) NOT NULL, architecture VARCHAR(255) NOT NULL, mTime DATETIME DEFAULT NULL, INDEX IDX_5CFE57CD5E237E0674995EFA (name, architecture), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE package (id INT AUTO_INCREMENT NOT NULL, repository_id INT DEFAULT NULL, files_id INT DEFAULT NULL, fileName VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, base VARCHAR(255) NOT NULL, version VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, groups LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', compressedSize BIGINT NOT NULL, installedSize BIGINT NOT NULL, md5sum VARCHAR(32) DEFAULT NULL, sha256sum VARCHAR(64) DEFAULT NULL, pgp_signature LONGBLOB DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, licenses LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', architecture VARCHAR(255) NOT NULL, buildDate DATETIME DEFAULT NULL, mTime DATETIME DEFAULT NULL, packager_name VARCHAR(255) DEFAULT NULL, packager_email VARCHAR(255) DEFAULT NULL, INDEX IDX_DE68679550C9D4F7 (repository_id), UNIQUE INDEX UNIQ_DE686795A3E65B2F (files_id), INDEX IDX_DE686795566E72BA (buildDate), INDEX IDX_DE6867955E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE packages_relation (id INT AUTO_INCREMENT NOT NULL, target_id INT DEFAULT NULL, source_id INT DEFAULT NULL, target_name VARCHAR(255) NOT NULL, target_version VARCHAR(255) DEFAULT NULL, dtype VARCHAR(255) NOT NULL, INDEX IDX_B3C62CBC158E0B66 (target_id), INDEX IDX_B3C62CBC953C1C61 (source_id), INDEX IDX_B3C62CBC933B68B7 (target_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE releng_release (version VARCHAR(191) NOT NULL, available TINYINT(1) NOT NULL, info LONGTEXT NOT NULL, iso_url VARCHAR(255) NOT NULL, md5sum VARCHAR(32) DEFAULT NULL, created DATETIME NOT NULL, kernel_version VARCHAR(255) DEFAULT NULL, release_date DATE NOT NULL, sha1sum VARCHAR(40) DEFAULT NULL, torrent_url VARCHAR(191) DEFAULT NULL, torrent_comment LONGTEXT DEFAULT NULL, torrent_info_hash VARCHAR(255) DEFAULT NULL, torrent_piece_length INT DEFAULT NULL, torrent_file_name VARCHAR(255) DEFAULT NULL, torrent_announce VARCHAR(255) DEFAULT NULL, torrent_file_length BIGINT DEFAULT NULL, torrent_piece_count SMALLINT DEFAULT NULL, torrent_created_by VARCHAR(255) DEFAULT NULL, torrent_creation_date DATETIME DEFAULT NULL, torrent_magnet_uri VARCHAR(255) DEFAULT NULL, INDEX IDX_BDBC9936A58FA485E769876D (available, release_date), PRIMARY KEY(version)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE TABLE files (id INT AUTO_INCREMENT NOT NULL, files LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('ALTER TABLE mirror ADD CONSTRAINT FK_5BA71B4AF92F3E70 FOREIGN KEY (country_id) REFERENCES country (code)');
            $this->addSql('ALTER TABLE package ADD CONSTRAINT FK_DE68679550C9D4F7 FOREIGN KEY (repository_id) REFERENCES repository (id)');
            $this->addSql('ALTER TABLE package ADD CONSTRAINT FK_DE686795A3E65B2F FOREIGN KEY (files_id) REFERENCES files (id)');
            $this->addSql('ALTER TABLE packages_relation ADD CONSTRAINT FK_B3C62CBC158E0B66 FOREIGN KEY (target_id) REFERENCES package (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE packages_relation ADD CONSTRAINT FK_B3C62CBC953C1C61 FOREIGN KEY (source_id) REFERENCES package (id)');
        } else {
            $this->warnIf(true, 'table doctrine_migration_versions not found in: ' . implode(', ', array_map(fn(OptionallyQualifiedName $table): string => $table->toString(), $knownTables)));
        }
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror DROP FOREIGN KEY FK_5BA71B4AF92F3E70');
        $this->addSql('ALTER TABLE package DROP FOREIGN KEY FK_DE68679550C9D4F7');
        $this->addSql('ALTER TABLE packages_relation DROP FOREIGN KEY FK_B3C62CBC158E0B66');
        $this->addSql('ALTER TABLE packages_relation DROP FOREIGN KEY FK_B3C62CBC953C1C61');
        $this->addSql('ALTER TABLE package DROP FOREIGN KEY FK_DE686795A3E65B2F');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP TABLE mirror');
        $this->addSql('DROP TABLE news_item');
        $this->addSql('DROP TABLE repository');
        $this->addSql('DROP TABLE package');
        $this->addSql('DROP TABLE packages_relation');
        $this->addSql('DROP TABLE releng_release');
        $this->addSql('DROP TABLE files');
    }

    #[\Override]
    public function isTransactional(): bool
    {
        return false;
    }
}
