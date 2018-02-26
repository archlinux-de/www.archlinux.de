<?php

namespace App\Command\Reset;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Entity\NewsItem;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Repository;
use App\Entity\Release;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetDatabaseCommand extends ContainerAwareCommand
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);

        $classNames = [];
        if ($input->getOption('packages')) {
            $classNames = array_merge($classNames, [AbstractRelation::class, Package::class, Repository::class]);
        }
        if ($input->getOption('countries')) {
            $classNames = array_merge($classNames, [Country::class]);
        }
        if ($input->getOption('mirrors')) {
            $classNames = array_merge($classNames, [Mirror::class]);
        }
        if ($input->getOption('news')) {
            $classNames = array_merge($classNames, [NewsItem::class]);
        }
        if ($input->getOption('releases')) {
            $classNames = array_merge($classNames, [Release::class]);
        }

        if (!empty($classNames)) {
            $this->resetDatabase($classNames);
        }

        $this->release();
    }

    /**
     * @param array $classNames
     * @param OutputInterface $output
     */
    private function resetDatabase(array $classNames)
    {
        $tables = [];
        foreach ($classNames as $className) {
            $tables[] = $this->entityManager->getClassMetadata($className)->getTableName();
        }

        $connection = $this->entityManager->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();

        switch ($connection->getDriver()->getName()) {
            case 'pdo_mysql':
                $connection->query('SET FOREIGN_KEY_CHECKS = 0');
                break;
            case 'pdo_sqlite':
                $connection->query('PRAGMA foreign_keys = OFF');
                break;
        }

        foreach ($tables as $table) {
            $connection->executeUpdate($dbPlatform->getTruncateTableSql($table));
        }
    }
}
