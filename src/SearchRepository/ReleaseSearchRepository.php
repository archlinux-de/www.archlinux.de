<?php

namespace App\SearchRepository;

use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\QueryString;
use Elastica\Query\Wildcard;
use FOS\ElasticaBundle\Repository;

class ReleaseSearchRepository extends Repository
{
    /**
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @return array<mixed>
     */
    public function findAllByQuery(int $offset, int $limit, string $query): array
    {
        $elasticQuery = new Query();
        $elasticQuery->addSort(['_score' => ['order' => 'desc']]);
        $elasticQuery->addSort(['releaseDate' => ['order' => 'desc']]);

        if ($query) {
            $boolQuery = new BoolQuery();
            $boolQuery->addShould(new Wildcard('version', '*' . $query . '*', 2));
            $boolQuery->addShould(new Wildcard('kernelVersion', '*' . $query . '*'));
            $boolQuery->addShould(new Wildcard('info', '*' . $query . '*'));
            $boolQuery->addShould(new QueryString($query));
            $boolQuery->setMinimumShouldMatch(1);
            $elasticQuery->setQuery($boolQuery);
        }

        $paginator = $this->createPaginatorAdapter($elasticQuery);
        $results = $paginator->getResults($offset, $limit);
        $releases = $results->toArray();

        return [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $results->getTotalHits(),
            'count' => count($releases),
            'items' => $releases
        ];
    }
}
