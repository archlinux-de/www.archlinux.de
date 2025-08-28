<?php

namespace App\DataFixtures;

use App\Entity\Release;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReleaseFixtures extends Fixture
{
    private const int NUMBER_OF_RELEASES = 50;
    private const array STATIC_VERSIONS = ['2022.01.01', '2019.02.03'];

    public function __construct(
        private readonly Generator $faker,
        private readonly ValidatorInterface $validator,
        private readonly KernelInterface $kernel,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $versions = self::STATIC_VERSIONS;
        for ($i = 0; $i < self::NUMBER_OF_RELEASES; $i++) {
            $versions[] = $this->faker->unique()->dateTimeThisDecade()->format('Y.m.d');
        }
        $versions = array_unique($versions);

        foreach ($versions as $version) {
            $release = new Release($version);
            $release->setAvailable($this->faker->boolean());
            $release->setInfo($this->faker->text());
            $release->setCreated($this->faker->dateTimeThisDecade());
            $release->setKernelVersion($this->faker->optional()->semver());
            $release->setReleaseDate($this->faker->dateTimeThisDecade());
            $release->setSha1Sum($this->faker->optional()->sha1());
            $release->setSha256Sum($this->faker->optional()->sha256());
            $release->setB2Sum($this->faker->optional()->regexify('[0-9a-f]{128}'));
            $release->setTorrentUrl($this->faker->optional()->url());
            $release->setFileName($this->faker->optional()->word() . '.iso');
            $release->setFileLength($this->faker->optional()->numberBetween(1000000, 2000000000));
            $release->setMagnetUri($this->faker->optional()->url());

            $errors = $this->validator->validate($release);
            if (count($errors) > 0) {
                throw new \RuntimeException((string) $errors);
            }

            $manager->persist($release);
        }

        $manager->flush();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run(new ArrayInput(['app:index:releases']));
    }
}
