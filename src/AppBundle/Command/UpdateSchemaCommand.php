<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSchemaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:config:update-schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        system('mysqldump -d --compact -u'
            . '\'' . escapeshellcmd($this->getContainer()->getParameter('database_user')) . '\''
            . ' '
            . escapeshellcmd(strlen($this->getContainer()->getParameter('database_password')) > 0
                ? '-p\'' . $this->getContainer()->getParameter('database_password') . '\'' : '')
            . ' '
            . '\'' . escapeshellcmd($this->getContainer()->getParameter('database_name')) . '\''
            . ' | sed  \'s/ AUTO_INCREMENT=[0-9]*//g\' > '
            . $this->getContainer()->getParameter('kernel.project_dir') . '/config/archportal_schema.sql');
    }
}
