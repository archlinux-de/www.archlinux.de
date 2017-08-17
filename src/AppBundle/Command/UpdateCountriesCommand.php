<?php

namespace AppBundle\Command;

use archportal\lib\Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCountriesCommand extends Command
{
    protected function configure()
    {
        $this->setName('app:config:update-countries');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $geoIP = new \GeoIP();
        $countries = array_combine($geoIP->GEOIP_COUNTRY_CODES, $geoIP->GEOIP_COUNTRY_NAMES);

        Database::beginTransaction();
        Database::query('DELETE FROM countries');

        $insertCountry = Database::prepare(
            '
        INSERT INTO
            countries
        SET
            code = :code,
            name = :name
        '
        );

        foreach ($countries as $code => $name) {
            $insertCountry->bindValue('code', $code, \PDO::PARAM_STR);
            $insertCountry->bindValue('name', $name, \PDO::PARAM_STR);
            $insertCountry->execute();
        }

        Database::commit();
    }
}
