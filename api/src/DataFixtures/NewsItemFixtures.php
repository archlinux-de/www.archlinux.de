<?php

namespace App\DataFixtures;

use App\Entity\NewsAuthor;
use App\Entity\NewsItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NewsItemFixtures extends Fixture
{
    private const int NUMBER_OF_NEWS = 50;
    private const array STATIC_NEWS = [18784 => 'Das Canterbury-Projekt'];

    public function __construct(
        private readonly Generator $faker,
        private readonly ValidatorInterface $validator,
        private readonly KernelInterface $kernel,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $titles = self::STATIC_NEWS;
        for ($i = 0; $i < self::NUMBER_OF_NEWS; $i++) {
            $titles[$this->faker->unique()->numberBetween(1, 100000)] = $this->faker->sentence();
        }

        foreach ($titles as $id => $title) {
            $newsItem = new NewsItem($id);
            $newsItem->setTitle($title);
            $newsItem->setLink($this->faker->unique()->url());
            /** @phpstan-ignore argument.type */
            $newsItem->setDescription(nl2br($this->faker->paragraphs(asText: true)));
            $newsItem->setLastModified($this->faker->dateTimeThisDecade());

            $author = new NewsAuthor();
            $author->setName($this->faker->name());
            $author->setUri($this->faker->optional()->url());
            $newsItem->setAuthor($author);

            $errors = $this->validator->validate($newsItem);
            if (count($errors) > 0) {
                throw new \RuntimeException((string) $errors);
            }

            $manager->persist($newsItem);
        }

        $manager->flush();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run(new ArrayInput(['app:index:news']));
    }
}
