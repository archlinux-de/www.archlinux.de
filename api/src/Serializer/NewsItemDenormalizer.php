<?php

namespace App\Serializer;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class NewsItemDenormalizer implements DenormalizerInterface, CacheableSupportsMethodInterface
{
    /** @var string */
    private $flarumUrl;

    /**
     * @param string $flarumUrl
     */
    public function __construct(string $flarumUrl)
    {
        $this->flarumUrl = $flarumUrl;
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
                foreach ($data['data'] as $discussions) {
                    $firstPostContent = $this->getFirstPost(
                        $data['included'],
                        $discussions['relationships']['firstPost']['data']['id']
                    )['attributes']['contentHtml'];

                    if (isset($discussions['relationships']['user']['data']['id'])) {
                        $user = $this->getUser($data['included'], $discussions['relationships']['user']['data']['id']);
                    } else {
                        $user = [
                            'attributes' => [
                                'displayName' => '[gelöscht]',
                                'slug' => null
                            ]
                        ];
                    }

                    $newsItem = (new NewsItem($discussions['id']))
                        ->setTitle($discussions['attributes']['title'])
                        ->setLink(sprintf('%s/d/%s', $this->flarumUrl, $discussions['attributes']['slug']))
                        ->setDescription($firstPostContent)
                        ->setAuthor(
                            (new NewsAuthor())
                                ->setUri(
                                    $user['attributes']['slug'] ? sprintf(
                                        '%s/u/%s',
                                        $this->flarumUrl,
                                        $user['attributes']['slug']
                                    ) : null
                                )
                                ->setName($user['attributes']['displayName'])
                        )
                        ->setLastModified(new \DateTime($discussions['attributes']['createdAt']));

                    yield $newsItem;
                }
            })()
        ];
    }

    private function getUser(array $included, int $id): array
    {
        foreach ($included as $item) {
            if ($item['type'] === 'users' && (int)$item['id'] === $id) {
                return $item;
            }
        }
        return [];
    }

    private function getFirstPost(array $included, int $id): array
    {
        foreach ($included as $item) {
            if ($item['type'] === 'posts' && (int)$item['id'] === $id) {
                return $item;
            }
        }
        return [];
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
