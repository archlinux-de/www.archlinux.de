<?php

namespace App\Tests\Serializer;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use App\Serializer\NewsItemNormalizer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Serializer;

class NewsItemNormalizerTest extends KernelTestCase
{
    private Serializer $serializer;

    public function setUp(): void
    {
        self::bootKernel();
        $this->serializer = static::getContainer()->get('serializer');
    }

    public function testSupportsNormalization(): void
    {
        $this->assertTrue(
            static::getContainer()->get(NewsItemNormalizer::class)->supportsNormalization(new NewsItem(1), 'json')
        );
    }

    public function testNormalize(): void
    {
        $newsAuthor = (new NewsAuthor())
            ->setName('Bob')
            ->setUri('http://localhost');
        $lastModified = new \DateTime('1982-02-05');
        $newsItem = (new NewsItem(1))
            ->setAuthor($newsAuthor)
            ->setTitle('Big Story')
            ->setDescription('Foo bar')
            ->setLink('https://www.archlinux.de/')
            ->setLastModified($lastModified);

        $json = $this->serializer->serialize($newsItem, 'json');
        $this->assertJson($json);
        $jsonArray = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(
            [
                'title' => 'Big Story',
                'author' => [
                    'name' => 'Bob',
                    'uri' => 'http://localhost'
                ],
                'lastModified' => '1982-02-05T00:00:00+00:00',
                'description' => 'Foo bar',
                'id' => 1,
                'link' => 'https://www.archlinux.de/',
                'slug' => 'Big-Story'
            ],
            $jsonArray
        );
    }
}
