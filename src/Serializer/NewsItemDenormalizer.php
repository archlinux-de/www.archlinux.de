<?php

namespace App\Serializer;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use App\Service\NewsItemSlugger;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class NewsItemDenormalizer implements DenormalizerInterface
{
    /** @var NewsItemSlugger */
    private $slugger;

    /**
     * @param NewsItemSlugger $slugger
     */
    public function __construct(NewsItemSlugger $slugger)
    {
        $this->slugger = $slugger;
    }

    /**
     * @param array<mixed> $data
     * @param string $type
     * @param string|null $format
     * @param array<mixed> $context
     * @return NewsItem[]
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return [
            ...(function () use ($data) {
                foreach ($data['entry'] as $newsEntry) {
                    $newsItem = new NewsItem($newsEntry['id']);
                    $newsItem
                        ->setTitle($newsEntry['title']['#'])
                        ->setLink($newsEntry['link']['@href'])
                        ->setDescription($newsEntry['summary']['#'])
                        ->setAuthor(
                            (new NewsAuthor())
                                ->setUri($newsEntry['author']['uri'])
                                ->setName($newsEntry['author']['name'])
                        )
                        ->setLastModified(new \DateTime($newsEntry['updated']));
                    $newsItem->setSlug($this->slugger->slugify($newsItem));
                    yield $newsItem;
                }
            })()
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type == NewsItem::class . '[]';
    }
}
