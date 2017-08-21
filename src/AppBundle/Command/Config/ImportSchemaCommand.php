<?php

namespace AppBundle\Command\Config;

use Doctrine\DBAL\Driver\Connection;
use Psr\SimpleCache\CacheInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Cache\Simple\PdoCache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSchemaCommand extends ContainerAwareCommand
{
    /** @var Connection */
    private $database;
    /** @var CacheInterface */
    private $cache;

    /**
     * @param Connection $connection
     * @param CacheInterface $cache
     */
    public function __construct(Connection $connection, CacheInterface $cache)
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
        if ($this->cache instanceof PdoCache) {
            $this->cache->createTable();
        }
    }
}
