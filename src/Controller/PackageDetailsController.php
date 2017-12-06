<?php

namespace App\Controller;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class PackageDetailsController extends Controller
{
    /** @var Connection */
    private $database;
    /** @var RouterInterface */
    private $router;

    /**
     * @param Connection $connection
     * @param RouterInterface $router
     */
    public function __construct(Connection $connection, RouterInterface $router)
    {
        $this->database = $connection;
        $this->router = $router;
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
        $packageRepository = $this->getDoctrine()->getRepository(Package::class);

        try {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->getDoctrine()->getManager();
            /** @var Package $package */
            $package = $entityManager
                ->createQueryBuilder()
                ->select('package', 'repository')
                ->from(Package::class, 'package')
                ->join('package.repository', 'repository')
                ->where('package.name = :pkgname')
                ->andWhere('repository.name = :repository')
                ->andWhere('repository.architecture = :architecture')
                ->setParameter('pkgname', $pkgname)
                ->setParameter('repository', $repo)
                ->setParameter('architecture', $arch)
                ->getQuery()
                ->getSingleResult();
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
