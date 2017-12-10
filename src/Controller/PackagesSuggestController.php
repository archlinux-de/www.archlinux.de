<?php

namespace App\Controller;

use App\Repository\PackageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PackagesSuggestController extends Controller
{
    /**
     * @Route("/packages/suggest", methods={"GET"})
     * @Cache(smaxage="600")
     * @param Request $request
     * @param PackageRepository $packageRepository
     * @return Response
     */
    public function suggestAction(Request $request, PackageRepository $packageRepository): Response
    {
        $term = $request->get('term');
        if (strlen($term) < 1 || strlen($term) > 50) {
            return $this->json([]);
        }
        $suggestions = $packageRepository->findByTerm($term, 10);

        return $this->json(array_column($suggestions, 'name'));
    }
}
