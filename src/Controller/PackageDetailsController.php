<?php

namespace App\Controller;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\CheckDependency;
use App\Entity\Packages\Relations\Conflict;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Relations\Replacement;
use App\Repository\FilesRepository;
use App\Repository\PackageRepository;
use App\Request\PaginationRequest;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PackageDetailsController extends AbstractController
{
    /** @var PackageRepository */
    private $packageRepository;

    /** @var FilesRepository */
    private $filesRepository;

    /** @var string */
    private $cgitUrl;

    /**
     * @param PackageRepository $packageRepository
     * @param FilesRepository $filesRepository
     * @param string $cgitUrl
     */
    public function __construct(PackageRepository $packageRepository, FilesRepository $filesRepository, string $cgitUrl)
    {
        $this->packageRepository = $packageRepository;
        $this->filesRepository = $filesRepository;
        $this->cgitUrl = $cgitUrl;
    }

    /**
     * @Route("/packages/{repo}/{arch}/{pkgname}", methods={"GET"})
     * @Cache(smaxage="600")
     * @param string $repo
     * @param string $arch
     * @param string $pkgname
     * @return Response
     * @throws NonUniqueResultException
     */
    public function indexAction(string $repo, string $arch, string $pkgname): Response
    {
        try {
            try {
                $package = $this->packageRepository->getByName($repo, $arch, $pkgname);
            } catch (NoResultException $e) {
                return $this->redirectToPackage(
                    $this->packageRepository->getByRepositoryArchitectureAndName($arch, $pkgname)
                );
            }
        } catch (NoResultException $f) {
            throw $this->createNotFoundException('Package not found', $f);
        }

        $cgitLink = $this->cgitUrl . (
            in_array(
                $package->getRepository()->getName(),
                array(
                    'community',
                    'community-testing',
                    'multilib',
                    'multilib-testing',
                )
            ) ? 'community' : 'packages'
            )
            . '.git/';

        return $this->render(
            'package/index.html.twig',
            [
                'package' => $package,
                'cgit_url' => $cgitLink,
                'inverse_depends' => $this->packageRepository->findByInverseRelationType($package, Dependency::class),
                'inverse_optdepends' => $this->packageRepository->findByInverseRelationType(
                    $package,
                    OptionalDependency::class
                ),
                'inverse_makedepends' => $this->packageRepository->findByInverseRelationType(
                    $package,
                    MakeDependency::class
                ),
            ]
        );
    }

    /**
     * @param Package $relatedPackage
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
     * @Route("/api/packages/{repo}/{arch}/{pkgname}/files", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param string $repo
     * @param string $arch
     * @param string $pkgname
     * @return Response
     * @throws NonUniqueResultException
     */
    public function filesAction(string $repo, string $arch, string $pkgname): Response
    {
        try {
            $files = $this->filesRepository->getByPackageName($repo, $arch, $pkgname);
        } catch (NoResultException $e) {
            throw $this->createNotFoundException('Package not found', $e);
        }

        return $this->json($files);
    }

    /**
     * @Route("/api/packages/{repo}/{arch}/{pkgname}", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param string $repo
     * @param string $arch
     * @param string $pkgname
     * @return Response
     */
    public function packageAction(string $repo, string $arch, string $pkgname): Response
    {
        return $this->json($this->packageRepository->getByName($repo, $arch, $pkgname));
    }

    /**
     * @Route("/api/packages/{repo}/{arch}/{pkgname}/inverse/{type}", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param string $repo
     * @param string $arch
     * @param string $pkgname
     * @param string $type
     * @param PaginationRequest $paginationRequest
     * @return Response
     */
    public function packageInverseDependencyAction(
        string $repo,
        string $arch,
        string $pkgname,
        string $type,
        PaginationRequest $paginationRequest
    ): Response {
        $types = [
            'check-dependency' => CheckDependency::class,
            'conflict' => Conflict::class,
            'dependency' => Dependency::class,
            'make-dependency' => MakeDependency::class,
            'optional-dependency' => OptionalDependency::class,
            'provision' => Provision::class,
            'replacement' => Replacement::class,
        ];
        if (!isset($types[$type])) {
            throw new BadRequestHttpException(sprintf('Invalid type: "%s"', $type));
        }

        return $this->json(
            $this->packageRepository->findInverseRelationsByQuery(
                $repo,
                $arch,
                $pkgname,
                $types[$type],
                $paginationRequest->getOffset(),
                $paginationRequest->getLimit()
            )
        );
    }
}
