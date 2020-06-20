<?php

namespace App\Serializer;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use App\Service\NewsItemIdParser;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class NewsItemDenormalizer implements DenormalizerInterface, CacheableSupportsMethodInterface
{
    /** @var NewsItemIdParser */
    private $newsItemIdParser;

    /**
     * @param NewsItemIdParser $newsItemIdParser
     */
    public function __construct(NewsItemIdParser $newsItemIdParser)
    {
        $this->newsItemIdParser = $newsItemIdParser;
    }

    /**
     * @param array $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @return NewsItem[]
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return [
            ...(function () use ($data) {
                foreach ($data['entry'] as $newsEntry) {
                    $newsItem = (new NewsItem($this->newsItemIdParser->parseId($newsEntry['id'])))
                        ->setTitle($newsEntry['title']['#'])
                        ->setLink($newsEntry['link']['@href'])
                        ->setDescription($newsEntry['summary']['#'])
                        ->setAuthor(
                            (new NewsAuthor())
                                ->setUri($newsEntry['author']['uri'] ?? null)
                                ->setName($newsEntry['author']['name'])
                        )
                        ->setLastModified(new \DateTime($newsEntry['updated']));
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

    /**
     * @return bool
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
