<?php

namespace AppBundle\Service;

use archportal\lib\Config;
use archportal\lib\Database;
use Symfony\Component\DependencyInjection\Container;

class LegacyEnvironment
{
    /** @var Container */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function initialize()
    {
        Database::setPdo($this->container->get('doctrine.orm.entity_manager')->getConnection()->getWrappedConnection());
        Database::setDatabase($this->container->getParameter('database_name'));

        foreach ($this->container->getParameterBag()->all() as $key => $value) {
            if (strpos($key, 'app.') === 0) {
                $sections = explode('.', $key);
                if (isset($sections[1]) && isset($sections[2])) {
                    Config::set($sections[1], $sections[2], $value);
                }
            }
        }
    }
}
