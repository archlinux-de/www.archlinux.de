<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Query\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{

    /**
     * @Route("/sitemap.xml", methods={"GET"})
     * @return Response
     */
    public function indexAction(): Response
    {
        $connection = $this->getDoctrine()->getConnection();
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder
            ->select([
                'repositories.name AS repository',
                'architectures.name AS architecture',
                'packages.name AS name',
                'packages.mtime'
            ])
            ->from('packages')
            ->from('repositories')
            ->from('architectures')
            ->where('packages.repository = repositories.id')
            ->andWhere('architectures.id = repositories.arch')
            ->andWhere('architectures.name = :architecture');
        $queryBuilder->setParameter(
            ':architecture',
            $this->getParameter('app.packages.default_architecture')
        );
        $packages = $queryBuilder->execute();

        $response = $this->render(
            'sitemap/index.xml.twig',
            ['packages' => $packages]
        )->setSharedMaxAge(600);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        return $response;
    }
}
