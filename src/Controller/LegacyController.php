<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class LegacyController extends Controller
{
    /** @var RouterInterface */
    private $router;
    /** @var array */
    private $internalPages = array(
        'GetFileFromMirror' => 'app_mirror_fallback',
        'GetOpenSearch' => 'app_getopensearch_index',
        'GetRecentNews' => 'app_recentnews_index',
        'GetRecentPackages' => 'app_recentpackages_index',
        'MirrorStatus' => 'app_mirrorstatus_index',
        'PackageDetails' => 'app_packagedetails_index',
        'Packages' => 'app_packages_index',
        'PackagesSuggest' => 'app_packagessuggest_suggest',
        'Start' => 'app_start_index'
    );
    /** @var array */
    private $externalPages = array(
        'ArchitectureDifferences' => 'https://www.archlinux.org/packages/differences/',
        'MirrorProblems' => 'https://www.archlinux.org/mirrors/status/#outofsync',
        'MirrorStatusJSON' => 'https://www.archlinux.org/mirrors/status/json/',
        'FunStatistics' => 'https://pkgstats.archlinux.de/fun',
        'ModuleStatistics' => 'https://pkgstats.archlinux.de/module',
        'PackageStatistics' => 'https://pkgstats.archlinux.de/package',
        'Statistics' => 'https://pkgstats.archlinux.de/'
    );

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @Route("/", condition="request.query.has('page')", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function pageAction(Request $request): Response
    {
        $page = $request->get('page');

        if (isset($this->internalPages[$page])) {
            $parameters = array_diff_key($request->query->all(), ['page' => '']);
            return $this->redirectToRoute($this->internalPages[$page], $parameters, Response::HTTP_MOVED_PERMANENTLY);
        } elseif (isset($this->externalPages[$page])) {
            return $this->redirect($this->externalPages[$page], Response::HTTP_MOVED_PERMANENTLY);
        }
        throw $this->createNotFoundException();
    }
}
