<?php

namespace App\Controller;

use App\Repository\MirrorRepository;
use App\Repository\ReleaseRepository;
use Doctrine\ORM\UnexpectedResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    /**
     * @Route("/download", methods={"GET"})
     * @Cache(smaxage="600")
     * @param ReleaseRepository $releaseRepository
     * @param MirrorRepository $mirrorRepository
     * @return Response
     */
    public function indexAction(ReleaseRepository $releaseRepository, MirrorRepository $mirrorRepository): Response
    {
        try {
            $release = $releaseRepository->getLatestAvailable();
        } catch (UnexpectedResultException $e) {
            throw $this->createNotFoundException('Release not found', $e);
        }

        $mirrors = $mirrorRepository->findBestByCountryAndLastSync(
            $this->getParameter('app.mirrors.country'),
            $release->getCreated()
        );

        return $this->render('download/index.html.twig', [
            'release' => $release,
            'mirrors' => $mirrors
        ]);
    }
}
