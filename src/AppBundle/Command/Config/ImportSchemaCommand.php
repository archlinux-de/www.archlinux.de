<?php

namespace AppBundle\Command\Config;

use archportal\lib\Database;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSchemaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:config:import-schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('AppBundle\Service\LegacyEnvironment')->initialize();
        Database::exec(
            file_get_contents(
                $this->getContainer()->getParameter('kernel.project_dir') . '/config/archportal_schema.sql'
            )
        );
    }
}
