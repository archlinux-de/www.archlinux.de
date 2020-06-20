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

    /**
     * @param PackageRepository $packageRepository
     * @param FilesRepository $filesRepository
     */
    public function __construct(PackageRepository $packageRepository, FilesRepository $filesRepository)
    {
        $this->packageRepository = $packageRepository;
        $this->filesRepository = $filesRepository;
    }

    /**
     * @Route("/api/packages/{repository}/{architecture}/{name}/files", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param string $repository
     * @param string $architecture
     * @param string $name
     * @return Response
     * @throws NonUniqueResultException
     */
    public function filesAction(string $repository, string $architecture, string $name): Response
    {
        try {
            $files = $this->filesRepository->getByPackageName($repository, $architecture, $name);
        } catch (NoResultException $e) {
            throw $this->createNotFoundException('Package not found', $e);
        }

        return $this->json($files);
    }

    /**
     * @Route("/api/packages/{repository}/{architecture}/{name}", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param string $repository
     * @param string $architecture
     * @param string $name
     * @return Response
     */
    public function packageAction(string $repository, string $architecture, string $name): Response
    {
        try {
            try {
                $package = $this->packageRepository->getByName($repository, $architecture, $name);
            } catch (NoResultException $e) {
                return $this->redirectToPackage(
                    $this->packageRepository->getByRepositoryArchitectureAndName($architecture, $name)
                );
            }
        } catch (NoResultException $f) {
            throw $this->createNotFoundException('Package not found', $f);
        }

        return $this->json($package);
    }

    /**
     * @param Package $relatedPackage
     * @return RedirectResponse
     */
    private function redirectToPackage(Package $relatedPackage): RedirectResponse
    {
        return $this->redirectToRoute(
            'app_packagedetails_package',
            [
                'repository' => $relatedPackage->getRepository()->getName(),
                'architecture' => $relatedPackage->getRepository()->getArchitecture(),
                'name' => $relatedPackage->getName()
            ]
        );
    }

    /**
     * @Route("/api/packages/{repository}/{architecture}/{name}/inverse-dependencies/{type}", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param string $repository
     * @param string $architecture
     * @param string $name
     * @param string $type
     * @return Response
     */
    public function packageInverseDependencyAction(
        string $repository,
        string $architecture,
        string $name,
        string $type
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
                $repository,
                $architecture,
                $name,
                $types[$type]
            )
        );
    }

    /**
     * @Route("/api/packages/{repository}/{architecture}/{name}/dependencies/{type}", methods={"GET"})
     * @Cache(maxage="300", smaxage="600")
     * @param string $repository
     * @param string $architecture
     * @param string $name
     * @param string $type
     * @return Response
     */
    public function packageDependencyAction(
        string $repository,
        string $architecture,
        string $name,
        string $type
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
            $this->packageRepository->findRelationsByQuery(
                $repository,
                $architecture,
                $name,
                $types[$type]
            )
        );
    }
}
