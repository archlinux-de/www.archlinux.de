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
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

class ResetDatabaseCommand extends Command
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var array */
    private $locks = [];

    /** @var CacheItemPoolInterface */
    private $cache;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(EntityManagerInterface $entityManager, CacheItemPoolInterface $cache)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->cache = $cache;
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $classNames = [];
        if ($input->getOption('packages')) {
            $this->lock('packages.lock');
            $classNames = array_merge(
                $classNames,
                [AbstractRelation::class, Files::class, Package::class, Repository::class]
            );

            $item = $this->cache->getItem('UpdatePackages-lastupdate')->set(0);
            $this->cache->save($item);
        }
        if ($input->getOption('countries')) {
            $this->lock('countries.lock');
            $classNames = array_merge($classNames, [Country::class]);
        }
        if ($input->getOption('mirrors')) {
            $this->lock('mirrors.lock');
            $classNames = array_merge($classNames, [Mirror::class]);
        }
        if ($input->getOption('news')) {
            $this->lock('news.lock');
            $classNames = array_merge($classNames, [NewsItem::class]);
        }
        if ($input->getOption('releases')) {
            $this->lock('releases.lock');
            $classNames = array_merge($classNames, [Release::class]);
        }

        if (!empty($classNames)) {
            $this->resetDatabase($classNames);
        }

        $this->release();

        return 0;
    }

    /**
     * @param string $name
     * @return bool
     * @codeCoverageIgnore
     */
    private function lock(string $name): bool
    {
        if (isset($this->locks[$name])) {
            throw new \LogicException(sprintf('A lock for "%s" is already in place.', $name));
        }

        if (SemaphoreStore::isSupported()) {
            $store = new SemaphoreStore();
        } else {
            $store = new FlockStore();
        }

        $this->locks[$name] = (new Factory($store))->createLock($name);
        if (!$this->locks[$name]->acquire()) {
            $this->locks[$name] = null;

            return false;
        }

        return true;
    }

    /**
     * @param array $classNames
     */
    private function resetDatabase(array $classNames): void
    {
        $tables = [];
        foreach ($classNames as $className) {
            $tables[] = $this->entityManager->getClassMetadata($className)->getTableName();
        }

        $connection = $this->entityManager->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();

        if ($connection->getDriver()->getName() == 'pdo_sqlite') {
            $connection->query('PRAGMA foreign_keys = OFF');
        } else {
            // @codeCoverageIgnoreStart
            $connection->query('SET FOREIGN_KEY_CHECKS = 0');
            // @codeCoverageIgnoreEnd
        }

        foreach ($tables as $table) {
            $connection->executeUpdate($dbPlatform->getTruncateTableSQL($table));
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private function release()
    {
        foreach ($this->locks as $name => $lock) {
            if ($lock) {
                $lock->release();
                $this->locks[$name] = null;
            }
        }
    }
}
