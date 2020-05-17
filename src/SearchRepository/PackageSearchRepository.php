<?php

namespace App\SearchRepository;

use App\Entity\Packages\Package;
use Elastica\Aggregation\Terms;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\FunctionScore;
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
        $elasticQuery->addSort(['popularity' => ['order' => 'desc']]);

        $boolQuery = new BoolQuery();

        if ($query) {
            $boolQuery->addShould(new Term(['name' => ['value' => $query, 'boost' => 7]]));
            $boolQuery->addShould(new Term(['base' => ['value' => $query, 'boost' => 6]]));
            $boolQuery->addShould(new Wildcard('name', '*' . $query . '*'));
            $boolQuery->addShould(new Wildcard('description', '*' . $query . '*'));
            $boolQuery->addShould(new QueryString($query));
            $boolQuery->setMinimumShouldMatch(1);
        }

        $boolQuery->addMust((new Term())->setTerm('repository.architecture', $architecture));

        if ($repository) {
            $boolQuery->addMust((new Term())->setTerm('repository.name', $repository));
        }

        $scoreQuery = new FunctionScore();
        $scoreQuery->setQuery($boolQuery);
        $scoreQuery->addFieldValueFactorFunction(
            'popularity',
            0.1,
            FunctionScore::FIELD_VALUE_FACTOR_MODIFIER_SQRT,
            0
        );

        $elasticQuery->setQuery($scoreQuery);
        $elasticQuery->addAggregation((new Terms('repository'))->setField('repository.name'));
        $elasticQuery->addAggregation((new Terms('architecture'))->setField('repository.architecture'));

        $paginator = $this->createPaginatorAdapter($elasticQuery);
        $results = $paginator->getResults($offset, $limit);
        $packages = $results->toArray();
        $aggregations = $results->getAggregations();

        return [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $results->getTotalHits(),
            'count' => count($packages),
            'items' => $packages,
            'repositories' => array_map(
                fn(array $repository): string => $repository['key'],
                $aggregations['repository']['buckets']
            ),
            'architectures' => array_map(
                fn(array $repository): string => $repository['key'],
                $aggregations['architecture']['buckets']
            )
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
