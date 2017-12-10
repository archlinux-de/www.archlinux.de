<?php

namespace App\Controller;

use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Repository\PackageRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PackageDetailsController extends Controller
{
    /**
     * @Route("/packages/{repo}/{arch}/{pkgname}", methods={"GET"})
     * @Cache(smaxage="600")
     * @param string $repo
     * @param string $arch
     * @param string $pkgname
     * @param PackageRepository $packageRepository
     * @return Response
     * @throws NonUniqueResultException
     */
    public function indexAction(
        string $repo,
        string $arch,
        string $pkgname,
        PackageRepository $packageRepository
    ): Response {
        try {
            $package = $packageRepository->getByName($repo, $arch, $pkgname);
        } catch (NoResultException $e) {
            throw $this->createNotFoundException('Package not found', $e);
        }

        $cgitUrl = $this->getParameter('app.packages.cgit') . (in_array($package->getRepository()->getName(), array(
                'community',
                'community-testing',
                'multilib',
                'multilib-testing',
            )) ? 'community' : 'packages')
            . '.git/';

        return $this->render('package/index.html.twig', [
            'package' => $package,
            'cgit_url' => $cgitUrl,
            'inverse_depends' => $packageRepository->findByInverseRelation($package, Dependency::class),
            'inverse_optdepends' => $packageRepository->findByInverseRelation($package, OptionalDependency::class),
            'inverse_makedepends' => $packageRepository->findByInverseRelation($package, MakeDependency::class),
        ]);
    }
}
