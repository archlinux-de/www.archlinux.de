<?php

namespace AppBundle\Command\Update;

use archportal\lib\Config;
use archportal\lib\Routing;
use archportal\lib\StatisticsPage;
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
        $this->getContainer()->get('AppBundle\Service\LegacyEnvironment')->initialize();

        if (Config::get('common', 'statistics')) {
            foreach (array(
                         'RepositoryStatistics',
                         'PackageStatistics',
                         'ModuleStatistics',
                         'UserStatistics',
                         'FunStatistics',
                     ) as $page) {
                $pageClass = Routing::getPageClass($page);
                /** @var StatisticsPage $pageObject */
                $pageObject = $this->getContainer()->get($pageClass);
                $pageObject->updateDatabaseCache();
            }
        }
    }
}
