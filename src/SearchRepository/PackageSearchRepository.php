<?php

namespace App\SearchRepository;

use App\Entity\Packages\Package;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\MatchPhrasePrefix;
use Elastica\Query\QueryString;
use Elastica\Query\Term;
use Elastica\Query\Wildcard;
use FOS\ElasticaBundle\Repository;

class PackageSearchRepository extends Repository
{
    /**
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @param string $architecture
     * @param string|null $repository
     * @return array<mixed>
     */
    public function findLatestByQueryAndArchitecture(
        int $offset,
        int $limit,
        string $query,
        string $architecture,
        ?string $repository
    ): array {
        $elasticQuery = new Query();
        $elasticQuery->addSort(['_score' => ['order' => 'desc']]);
        $elasticQuery->addSort(['buildDate' => ['order' => 'desc']]);

        $boolQuery = new BoolQuery();

        if ($query) {
            $boolQuery->addShould(new Term(['name' => ['value' => $query, 'boost' => 7]]));
            $boolQuery->addShould(new Wildcard('name', '*' . $query . '*'));
            $boolQuery->addShould(new Wildcard('description', '*' . $query . '*'));
            $boolQuery->addShould(new QueryString($query));
            $boolQuery->setMinimumShouldMatch(1);
        }

        $boolQuery->addMust((new Term())->setTerm('repository.architecture', $architecture));

        if ($repository) {
            $boolQuery->addMust((new Term())->setTerm('repository.name', $repository));
        }

        $elasticQuery->setQuery($boolQuery);

        $paginator = $this->createPaginatorAdapter($elasticQuery);
        $results = $paginator->getResults($offset, $limit);
        $packages = $results->toArray();

        return [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $results->getTotalHits(),
            'count' => count($packages),
            'items' => $packages
        ];
    }

    /**
     * @param string $term
     * @param int $limit
     * @return Package[]
     */
    public function findByTerm(string $term, int $limit): array
    {
        $query = new MatchPhrasePrefix('name', $term);
        return $this->find($query, $limit);
    }
}
