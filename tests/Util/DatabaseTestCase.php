<?php

namespace App\Tests\Util;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DatabaseTestCase extends KernelTestCase
{
    /** @var ContainerInterface */
    private $container;

    public function setUp()
    {
        parent::setUp();
        $this->container = static::bootKernel()->getContainer();
        $this->createDatabase();
    }

    private function createDatabase(): void
    {
        $entityManager = $this->getEntityManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metadata);
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    public function tearDown()
    {
        $this->container = null;
        parent::tearDown();
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository(string $className): ObjectRepository
    {
        return $this->getEntityManager()->getRepository($className);
    }

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        return $this->getContainer()->get('test.client');
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
