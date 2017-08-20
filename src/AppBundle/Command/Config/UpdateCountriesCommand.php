<?php

namespace AppBundle\Command\Config;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCountriesCommand extends ContainerAwareCommand
{
    /** @var Connection */
    private $database;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->database = $connection;
    }

    protected function configure()
    {
        $this->setName('app:config:update-countries');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $geoIP = new \GeoIP();
        $countries = array_combine($geoIP->GEOIP_COUNTRY_CODES, $geoIP->GEOIP_COUNTRY_NAMES);

        $this->database->beginTransaction();
        $this->database->query('DELETE FROM countries');

        $insertCountry = $this->database->prepare(
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

        $this->database->commit();
    }
}
