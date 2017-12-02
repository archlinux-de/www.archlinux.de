<?php

namespace App\Controller;

use App\Entity\Mirror;
use App\Entity\Release;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DownloadController extends Controller
{
    /**
     * @Route("/download", methods={"GET"})
     * @Cache(smaxage="600")
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function indexAction(EntityManagerInterface $entityManager): Response
    {
        /** @var Release $release */
        $release = $entityManager
            ->createQueryBuilder()
            ->select('release')
            ->from(Release::class, 'release')
            ->where('release.available = true')
            ->orderBy('release.releaseDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        $mirrors = $entityManager->createQueryBuilder()
            ->select('mirror.url')
            ->from(Mirror::class, 'mirror')
            ->where('mirror.protocol = :protocol')
            ->andWhere('mirror.country = :country')
            ->andWhere('mirror.lastSync > :lastsync')
            ->orderBy('mirror.score')
            ->setParameter('protocol', 'https')
            ->setParameter('country', $this->getParameter('app.mirrors.country'))
            ->setParameter('lastsync', $release->getCreated())
            ->getQuery()
            ->getResult();

        return $this->render('download/index.html.twig', [
            'release' => $release,
            'mirrors' => $mirrors
        ]);
    }
}
