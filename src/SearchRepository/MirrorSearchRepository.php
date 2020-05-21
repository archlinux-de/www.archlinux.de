<?php

namespace App\SearchRepository;

use App\Entity\Mirror;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Exists;
use Elastica\Query\QueryString;
use Elastica\Query\Range;
use Elastica\Query\Term;
use Elastica\Query\Wildcard;

class MirrorSearchRepository
{
    private const PROTOCOL = 'https';

    /** @var string */
    private $defaultCountry;

    /**
     * @param string $defaultCountry
     * @return MirrorSearchRepository
     */
    public function setDefaultCountry(string $defaultCountry): MirrorSearchRepository
    {
        $this->defaultCountry = $defaultCountry;
        return $this;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string $query
     * @return array<mixed>
     */
    public function findSecureByQuery(int $offset, int $limit, string $query): array
    {
        $elasticQuery = new Query();
        $elasticQuery->addSort(['_score' => ['order' => 'desc']]);
        $elasticQuery->addSort(['score' => ['order' => 'asc']]);

        $boolQuery = new BoolQuery();

        if ($query) {
            $boolQuery->addShould(new Wildcard('url', '*' . $query . '*'));
            $boolQuery->addShould(new Wildcard('country.name', '*' . $query . '*'));
            $boolQuery->addShould(new QueryString($query));
            $boolQuery->setMinimumShouldMatch(1);
        }

        $boolQuery->addShould((new Term())->setTerm('country.code', $this->defaultCountry, 0.1));

        $boolQuery->addMust((new Term())->setTerm('active', 'true'));
        $boolQuery->addMust((new Term())->setTerm('protocol', self::PROTOCOL));
        $boolQuery->addMust(new Exists('score'));

        $elasticQuery->setQuery($boolQuery);

        $paginator = $this->createPaginatorAdapter($elasticQuery);
        $results = $paginator->getResults($offset, $limit);
        $mirrors = $results->toArray();

        return [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $results->getTotalHits(),
            'count' => count($mirrors),
            'items' => $mirrors
        ];
    }

    /**
     * @param string $countryCode
     * @param \DateTime $lastSync
     * @return Mirror[]
     */
    public function findBestByCountryAndLastSync(string $countryCode, \DateTime $lastSync): array
    {
        $elasticQuery = new Query();
        $elasticQuery->addSort(['_score' => ['order' => 'desc']]);
        $elasticQuery->addSort(['score' => ['order' => 'asc']]);

        $boolQuery = new BoolQuery();

        $boolQuery->addShould((new Term())->setTerm('country.code', $countryCode));
        $boolQuery->addShould(
            (new Range())->addField(
                'lastSync',
                [
                    'gt' => $lastSync->getTimestamp(),
                    'format' => 'epoch_second',
                    'boost' => 2
                ]
            )
        );

        $boolQuery->addMust((new Term())->setTerm('active', 'true'));
        $boolQuery->addMust((new Term())->setTerm('protocol', self::PROTOCOL));
        $boolQuery->addMust(new Exists('score'));

        $elasticQuery->setQuery($boolQuery);

        return $this->find($elasticQuery);
    }
}
