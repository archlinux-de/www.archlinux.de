<?php

namespace AppBundle\Command\Update;

use archportal\cronjobs\UpdatePackages;
use archportal\cronjobs\UpdateReleases;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends ContainerAwareCommand
{
    use LockableTrait;

    protected function configure()
    {
        $this
            ->setName('app:update')
            ->addArgument('job', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);
        $this->getContainer()->get('AppBundle\Service\LegacyEnvironment')->initialize();

        $job = $input->getArgument('job');
        switch ($job) {
            case 'packages':
                UpdatePackages::run();
                break;
            case 'releases':
                UpdateReleases::run();
                break;
            default:
                throw new InvalidArgumentException($job);
        }
    }
}
