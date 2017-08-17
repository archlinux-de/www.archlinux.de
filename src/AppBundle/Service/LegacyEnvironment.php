<?php

namespace AppBundle\Service;

use archportal\lib\Config;
use archportal\lib\Database;
use archportal\lib\Input;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

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

    public function initialize(Request $request = null)
    {
        Database::setConnection($this->container->get('doctrine.dbal.default_connection'));

        foreach ($this->container->getParameterBag()->all() as $key => $value) {
            if (strpos($key, 'app.') === 0) {
                $sections = explode('.', $key);
                if (isset($sections[1]) && isset($sections[2])) {
                    Config::set($sections[1], $sections[2], $value);
                }
            }
        }

        if (is_null($request)) {
            $request = Request::createFromGlobals();
        }
        Input::setHttpRequest($request);
    }
}
