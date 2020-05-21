<?php

namespace App\SearchRepository;

use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\QueryString;
use Elastica\Query\Wildcard;

class NewsItemSearchRepository
{
    /**
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @return array<mixed>
     */
    public function findLatestByQuery(int $offset, int $limit, string $query): array
    {
        $elasticQuery = new Query();
        $elasticQuery->addSort(['_score' => ['order' => 'desc']]);
        $elasticQuery->addSort(['lastModified' => ['order' => 'desc']]);

        if ($query) {
            $boolQuery = new BoolQuery();
            $boolQuery->addShould(new Wildcard('title', '*' . $query . '*', 2));
            $boolQuery->addShould(new Wildcard('description', '*' . $query . '*'));
            $boolQuery->addShould(new QueryString($query));
            $boolQuery->setMinimumShouldMatch(1);
            $elasticQuery->setQuery($boolQuery);
        }

        $paginator = $this->createPaginatorAdapter($elasticQuery);
        $results = $paginator->getResults($offset, $limit);
        $newsItems = $results->toArray();

        return [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $results->getTotalHits(),
            'count' => count($newsItems),
            'items' => $newsItems
        ];
    }
}
