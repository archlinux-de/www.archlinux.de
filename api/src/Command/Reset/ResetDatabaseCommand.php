<?php

namespace App\Command\Reset;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Entity\NewsItem;
use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Repository;
use App\Entity\Release;
use App\Service\PackageDatabaseMirror;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

class ResetDatabaseCommand extends Command
{
    /** @var array<LockInterface|null> */
    private array $locks = [];

    public function __construct(private EntityManagerInterface $entityManager, private CacheItemPoolInterface $cache)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:reset:database')
            ->addOption('packages')
            ->addOption('countries')
            ->addOption('mirrors')
            ->addOption('news')
            ->addOption('releases');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $classNames = [];
        if ($input->getOption('packages')) {
            $this->lock('packages.lock');
            $classNames = array_merge(
                $classNames,
                [AbstractRelation::class, Files::class, Package::class, Repository::class]
            );

            $this->cache->deleteItem(PackageDatabaseMirror::CACHE_KEY);
        }
        if ($input->getOption('countries')) {
            $this->lock('countries.lock');
            $classNames = [...$classNames, ...[Country::class]];
        }
        if ($input->getOption('mirrors')) {
            $this->lock('mirrors.lock');
            $classNames = [...$classNames, ...[Mirror::class]];
        }
        if ($input->getOption('news')) {
            $this->lock('news.lock');
            $classNames = [...$classNames, ...[NewsItem::class]];
        }
        if ($input->getOption('releases')) {
            $this->lock('releases.lock');
            $classNames = [...$classNames, ...[Release::class]];
        }

        if (!empty($classNames)) {
            $this->resetDatabase($classNames);
        }

        $this->release();

        return Command::SUCCESS;
    }

    /**
     * @codeCoverageIgnore
     */
    private function lock(string $name): void
    {
        if (isset($this->locks[$name])) {
            throw new \LogicException(sprintf('A lock for "%s" is already in place.', $name));
        }

        if (SemaphoreStore::isSupported()) {
            $store = new SemaphoreStore();
        } else {
            $store = new FlockStore();
        }

        $this->locks[$name] = (new LockFactory($store))->createLock($name);
        if (!$this->locks[$name]->acquire()) {
            $this->locks[$name] = null;
        }
    }

    /**
     * @param class-string[] $classNames
     */
    private function resetDatabase(array $classNames): void
    {
        $tables = [];
        foreach ($classNames as $className) {
            $tables[] = $this->entityManager->getClassMetadata($className)->getTableName();
        }

        $connection = $this->entityManager->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();

        if ($connection->getDriver()->getDatabasePlatform() instanceof SqlitePlatform) {
            $connection->executeQuery('PRAGMA foreign_keys = OFF');
        } else {
            // @codeCoverageIgnoreStart
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');
            // @codeCoverageIgnoreEnd
        }

        foreach ($tables as $table) {
            $connection->executeStatement($dbPlatform->getTruncateTableSQL($table));
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private function release(): void
    {
        foreach ($this->locks as $name => $lock) {
            if ($lock) {
                $lock->release();
                $this->locks[$name] = null;
            }
        }
    }
}
