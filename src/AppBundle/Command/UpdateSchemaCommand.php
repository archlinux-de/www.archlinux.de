<?php

namespace AppBundle\Command;

use archportal\lib\Config;
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
            . '\'' . escapeshellcmd(Config::get('Database', 'user')) . '\''
            . ' '
            . escapeshellcmd(strlen(Config::get('Database', 'password')) > 0 ? '-p\'' . Config::get('Database',
                    'password') . '\'' : '')
            . ' '
            . '\'' . escapeshellcmd(Config::get('Database', 'database')) . '\''
            . ' | sed  \'s/ AUTO_INCREMENT=[0-9]*//g\' > '
            . $this->getContainer()->getParameter('kernel.project_dir') . '/config/archportal_schema.sql'
        );
    }
}
