<?php

namespace App\DataFixtures;

use App\Entity\Country;
use App\Entity\Mirror;
use App\Entity\MirrorPopularity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MirrorFixtures extends Fixture implements DependentFixtureInterface
{
    private const int NUMBER_OF_MIRRORS = 50;
    private const array STATIC_URLS = ['https://geo.mirror.pkgbuild.com/'];

    public function __construct(
        private readonly Generator $faker,
        private readonly ValidatorInterface $validator,
        private readonly KernelInterface $kernel,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $urls = self::STATIC_URLS;
        for ($i = 0; $i < self::NUMBER_OF_MIRRORS; $i++) {
            $urls[] = str_replace('http://', 'https://', $this->faker->unique()->url());
        }

        foreach ($urls as $url) {
            $mirror = new Mirror($url);
            $mirror->setCountry($this->getReference($this->faker->countryCode(), Country::class));
            $mirror->setLastSync($this->faker->dateTimeThisDecade());
            $mirror->setDelay($this->faker->numberBetween(0, 3600));
            $mirror->setDurationAvg($this->faker->randomFloat(4, 0, 10));
            $mirror->setScore($this->faker->randomFloat(4, 0, 100));
            $mirror->setCompletionPct($this->faker->randomFloat(4, 0, 1));
            $mirror->setDurationStddev($this->faker->randomFloat(4, 0, 5));
            $mirror->setIpv4($this->faker->boolean());
            $mirror->setIpv6($this->faker->boolean());

            $popularity = new MirrorPopularity(
                $this->faker->randomFloat(4, 0, 100),
                $this->faker->numberBetween(0, 1000000),
                $this->faker->numberBetween(0, 1000000)
            );
            $mirror->setPopularity($popularity);

            $errors = $this->validator->validate($mirror);
            if (count($errors) > 0) {
                throw new \RuntimeException((string) $errors);
            }

            $manager->persist($mirror);
        }

        $manager->flush();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run(new ArrayInput(['app:index:mirrors']));
    }

    public function getDependencies(): array
    {
        return [
            CountryFixtures::class,
        ];
    }
}
