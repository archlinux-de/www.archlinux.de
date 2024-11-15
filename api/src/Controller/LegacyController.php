<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LegacyController extends AbstractController
{
    /** @var array<string, string> */
    private array $internalPages = [
        'GetFileFromMirror' => 'app_mirror_fallback',
        'GetOpenSearch' => 'app_packages_opensearch',
        'GetRecentNews' => 'app_news_feed',
        'GetRecentPackages' => 'app_packages_feed',
        'MirrorStatus' => 'app_mirrors',
        'PackageDetails' => 'app_package',
        'Packages' => 'app_packages',
        'PackagesSuggest' => 'app_packages_suggest',
        'Start' => 'app_start'
    ];

    /** @var array<string, string> */
    private array $externalPages = [
        'ArchitectureDifferences' => 'https://www.archlinux.org/packages/differences/',
        'MirrorProblems' => 'https://www.archlinux.org/mirrors/status/#outofsync',
        'MirrorStatusJSON' => 'https://www.archlinux.org/mirrors/status/json/',
        'FunStatistics' => 'https://pkgstats.archlinux.de/fun',
        'ModuleStatistics' => 'https://pkgstats.archlinux.de/module',
        'PackageStatistics' => 'https://pkgstats.archlinux.de/package',
        'Statistics' => 'https://pkgstats.archlinux.de/'
    ];

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    #[Route(
        path: '/',
        methods: ['GET'],
        condition: 'request.getQueryString() matches "/page(%3D|=).+/"'
    )]
    public function pageAction(Request $request): Response
    {
        $queryString = str_replace(';', '&', urldecode($request->getQueryString() ?? ''));

        $queries = [];
        parse_str($queryString, $queries);

        $page = $queries['page'] ?? '';
        assert(is_string($page));

        if (isset($this->internalPages[$page])) {
            $parameters = array_diff_key($queries, ['page' => '']);

            if (
                $this->internalPages[$page] == 'app_package'
                && isset($parameters['repo'])
                && isset($parameters['arch'])
                && isset($parameters['pkgname'])
            ) {
                $parameters = [
                    'repository' => $parameters['repo'],
                    'architecture' => $parameters['arch'],
                    'name' => $parameters['pkgname']
                ];
            }

            try {
                return $this->redirectToRoute(
                    $this->internalPages[$page],
                    $parameters,
                    Response::HTTP_MOVED_PERMANENTLY
                );
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning($e->getMessage(), ['exception' => $e]);
            }
        } elseif (isset($this->externalPages[$page])) {
            return $this->redirect($this->externalPages[$page], Response::HTTP_MOVED_PERMANENTLY);
        }

        throw $this->createNotFoundException();
    }
}
