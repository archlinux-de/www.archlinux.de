<?php

namespace AppBundle\Command\Update;

use AppBundle\Controller\Statistics\FunStatisticsController;
use AppBundle\Controller\Statistics\ModuleStatisticsController;
use AppBundle\Controller\Statistics\PackageStatisticsController;
use AppBundle\Controller\Statistics\RepositoryStatisticsController;
use AppBundle\Controller\Statistics\UserStatisticsController;
use archportal\lib\IDatabaseCachable;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePkgstatsCommand extends ContainerAwareCommand
{
    use LockableTrait;

    protected function configure()
    {
        $this->setName('app:update:pkgstats');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);

        foreach (array(
                     RepositoryStatisticsController::class,
                     PackageStatisticsController::class,
                     ModuleStatisticsController::class,
                     UserStatisticsController::class,
                     FunStatisticsController::class,
                 ) as $statisticsControllerName) {
            /** @var IDatabaseCachable $statisticsController */
            $statisticsController = $this->getContainer()->get($statisticsControllerName);
            $statisticsController->updateDatabaseCache();
        }
    }
}
