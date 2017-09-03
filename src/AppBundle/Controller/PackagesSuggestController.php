<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Packages\Package;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PackagesSuggestController extends Controller
{
    /**
     * @Route("/packages/suggest", methods={"GET"})
     * @Cache(smaxage="600")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function suggestAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $term = $request->get('term');
        if (strlen($term) < 1 || strlen($term) > 50) {
            return $this->json([]);
        }
        $suggestions = $entityManager
            ->createQueryBuilder()
            ->select('package.name')
            ->distinct()
            ->from(Package::class, 'package')
            ->where('package.name LIKE :package')
            ->orderBy('package.name')
            ->setMaxResults(10)
            ->setParameter('package', $term . '%')
            ->getQuery()
            ->getScalarResult();

        return $this->json(array_column($suggestions, 'name'));
    }
}
