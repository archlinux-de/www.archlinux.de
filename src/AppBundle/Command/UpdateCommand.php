<?php

namespace AppBundle\Command;

use archportal\cronjobs\UpdateMirrors;
use archportal\cronjobs\UpdateNews;
use archportal\cronjobs\UpdatePackages;
use archportal\cronjobs\UpdatePkgstats;
use archportal\cronjobs\UpdateReleases;
use archportal\lib\Database;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:update')
            ->addArgument('job', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Database::setPdo(
            $this->getContainer()->get('doctrine.orm.entity_manager')->getConnection()->getWrappedConnection()
        );
        Database::setDatabase($this->getContainer()->getParameter('database_name'));

        $job = $input->getArgument('job');
        switch ($job) {
            case 'mirrors':
                UpdateMirrors::run();
                break;
            case 'news':
                UpdateNews::run();
                break;
            case 'packages':
                UpdatePackages::run();
                break;
            case 'pkgstats':
                UpdatePkgstats::run();
                break;
            case 'releases':
                UpdateReleases::run();
                break;
            default:
                throw new InvalidArgumentException($job);
        }
    }
}
