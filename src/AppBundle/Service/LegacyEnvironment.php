<?php

namespace AppBundle\Service;

use archportal\lib\Database;
use Doctrine\ORM\EntityManagerInterface;

class LegacyEnvironment
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var string */
    private $databaseName;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string $databaseName
     */
    public function __construct(EntityManagerInterface $entityManager, string $databaseName)
    {
        $this->entityManager = $entityManager;
        $this->databaseName = $databaseName;
    }

    public function initialize()
    {
        Database::setPdo($this->entityManager->getConnection()->getWrappedConnection());
        Database::setDatabase($this->databaseName);
    }
}
