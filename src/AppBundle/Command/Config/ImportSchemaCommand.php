<?php

namespace AppBundle\Command\Config;

use Doctrine\DBAL\Driver\Connection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSchemaCommand extends ContainerAwareCommand
{
    /** @var Connection */
    private $database;
    /** @var CacheItemPoolInterface */
    private $cache;

    /**
     * @param Connection $connection
     * @param PdoAdapter $cache
     */
    public function __construct(Connection $connection, PdoAdapter $cache)
    {
        parent::__construct();
        $this->database = $connection;
        $this->cache = $cache;
    }

    protected function configure()
    {
        $this->setName('app:config:import-schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->database->exec(
            file_get_contents(
                $this->getContainer()->getParameter('kernel.project_dir') . '/app/config/archportal_schema.sql'
            )
        );
        $this->cache->createTable();
    }
}
