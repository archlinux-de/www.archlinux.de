<?php

namespace AppBundle\Controller;

use archportal\lib\Database;
use archportal\lib\Input;
use archportal\lib\Page;
use archportal\lib\Routing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyController extends Controller
{
    /**
     * @Route("/", name="legacy")
     */
    public function indexAction(Request $request): Response
    {
        Database::setPdo($this->get('doctrine.orm.entity_manager')->getConnection()->getWrappedConnection());
        $page = Routing::getPageClass(Input::get()->getString('page', 'Start'));
        /** @var Page $thisPage */
        $thisPage = new $page();

        $thisPage->prepare();
        ob_start();
        $thisPage->printPage();
        $pageContent = ob_get_clean();

        return new Response(
            $pageContent,
            $thisPage->getStatus(),
            array_merge(['Content-Type' => $thisPage->getContentType()], $thisPage->getHeaders())
        );
    }
}
