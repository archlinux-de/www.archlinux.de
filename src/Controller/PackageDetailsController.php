<?php

namespace App\Controller;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Repository\FilesRepository;
use App\Repository\PackageRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PackageDetailsController extends AbstractController
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
            try {
                $package = $packageRepository->getByName($repo, $arch, $pkgname);
            } catch (NoResultException $e) {
                return $this->redirectToPackage(
                    $packageRepository->getByRepositoryArchitectureAndName($arch, $pkgname)
                );
            }
        } catch (NoResultException $f) {
            throw $this->createNotFoundException('Package not found', $f);
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
            'inverse_depends' => $packageRepository->findByInverseRelationType($package, Dependency::class),
            'inverse_optdepends' => $packageRepository->findByInverseRelationType($package, OptionalDependency::class),
            'inverse_makedepends' => $packageRepository->findByInverseRelationType($package, MakeDependency::class),
        ]);
    }

    /**
     * @param $relatedPackage
     * @return RedirectResponse
     */
    private function redirectToPackage(Package $relatedPackage): RedirectResponse
    {
        return $this->redirectToRoute(
            'app_packagedetails_index',
            [
                'repo' => $relatedPackage->getRepository()->getName(),
                'arch' => $relatedPackage->getRepository()->getArchitecture(),
                'pkgname' => $relatedPackage->getName()
            ]
        );
    }

    /**
     * @Route("/packages/{repo}/{arch}/{pkgname}/files", methods={"GET"})
     * @Cache(smaxage="600")
     * @param string $repo
     * @param string $arch
     * @param string $pkgname
     * @param FilesRepository $filesRepository
     * @return Response
     * @throws NonUniqueResultException
     */
    public function filesAction(
        string $repo,
        string $arch,
        string $pkgname,
        FilesRepository $filesRepository
    ): Response {
        try {
            $files = $filesRepository->getByPackageName($repo, $arch, $pkgname);
        } catch (NoResultException $e) {
            throw $this->createNotFoundException('Package not found', $e);
        }

        return $this->json($files);
    }
}
