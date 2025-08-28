<?php

namespace App\DataFixtures;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Popularity;
use App\Entity\Packages\Relations\CheckDependency;
use App\Entity\Packages\Relations\Conflict;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Relations\Replacement;
use App\Entity\Packages\Repository;
use App\Repository\AbstractRelationRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PackageFixtures extends Fixture implements DependentFixtureInterface
{
    private const int NUMBER_OF_PACKAGES = 50;
    private const array STATIC_PACKAGES = ['pacman', 'namcap', 'linux'];

    /**
     * @param array<string, string[]> $repositoryConfiguration
     */
    public function __construct(
        private readonly Generator $faker,
        private readonly ValidatorInterface $validator,
        private readonly KernelInterface $kernel,
        private readonly AbstractRelationRepository $abstractRelationRepository,
        private readonly array $repositoryConfiguration,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $repositories = [];
        foreach ($this->repositoryConfiguration as $name => [$architecture]) {
            $repositories[] = $this->getReference(sprintf('repository-%s-%s', $name, $architecture), Repository::class);
        }

        $packages = [];
        $names = self::STATIC_PACKAGES;
        for ($i = 0; $i < self::NUMBER_OF_PACKAGES; $i++) {
            $names[] = $this->faker->unique()->word();
        }
        $names = array_unique($names);

        foreach ($names as $name) {
            /** @var Repository $repository */
            $repository = $this->faker->randomElement($repositories);

            $package = new Package(
                $repository,
                $name,
                $this->faker->semver() . '-' . $this->faker->numberBetween(1, 9),
                Architecture::X86_64
            );

            $package->setDescription($this->faker->sentence());
            $package->setGroups(
                /** @phpstan-ignore argument.type */
                $this->faker->randomElements(['base', 'devel', 'gnome', 'kde'], $this->faker->numberBetween(0, 4))
            );
            $package->setCompressedSize($this->faker->numberBetween(1000, 100000000));
            $package->setInstalledSize($this->faker->numberBetween(10000, 500000000));
            $package->setSha256sum($this->faker->sha256());
            $package->setUrl($this->faker->url());
            $package->setLicenses(
                /** @phpstan-ignore argument.type */
                $this->faker->randomElements(['GPL', 'MIT', 'LGPL'], $this->faker->numberBetween(0, 3))
            );
            $package->setBuildDate($this->faker->dateTimeThisYear());

            $packager = new Packager($this->faker->name(), $this->faker->optional()->email());
            $package->setPackager($packager);

            $popularity = new Popularity(
                $this->faker->randomFloat(4, 0, 100),
                $this->faker->numberBetween(0, 1000000),
                $this->faker->numberBetween(0, 1000000)
            );
            $package->setPopularity($popularity);

            // Assign files
            $files = [];
            for ($j = 0; $j < $this->faker->numberBetween(1, 10); $j++) {
                $files[] = $this->faker->filePath();
            }
            /** @var string[] $files */
            $filesEntity = Files::createFromArray($files);
            $package->setFiles($filesEntity);

            $errors = $this->validator->validate($package);
            if (count($errors) > 0) {
                throw new \RuntimeException((string) $errors);
            }

            $manager->persist($package);
            $packages[] = $package;
        }

        $manager->flush();

        // Add relations in a second loop
        foreach ($packages as $package) {
            // Add random dependencies
            if ($this->faker->boolean(70)) { // 70% chance to have dependencies
                $numDependencies = $this->faker->numberBetween(1, 5);
                for ($j = 0; $j < $numDependencies; $j++) {
                    /** @var Package $targetPackage */
                    $targetPackage = $this->faker->randomElement($packages);
                    $dependency = new Dependency(
                        $targetPackage->getName(),
                        null
                    );
                    $package->addDependency($dependency);
                }
            }

            // Add random conflicts
            if ($this->faker->boolean(20)) { // 20% chance to have conflicts
                $numConflicts = $this->faker->numberBetween(1, 3);
                for ($j = 0; $j < $numConflicts; $j++) {
                    /** @var Package $targetPackage */
                    $targetPackage = $this->faker->randomElement($packages);
                    $conflict = new Conflict(
                        $targetPackage->getName(),
                        null
                    );
                    $package->addConflict($conflict);
                }
            }

            // Add random replacements
            if ($this->faker->boolean(10)) { // 10% chance to have replacements
                $numReplacements = $this->faker->numberBetween(1, 2);
                for ($j = 0; $j < $numReplacements; $j++) {
                    /** @var Package $targetPackage */
                    $targetPackage = $this->faker->randomElement($packages);
                    $replacement = new Replacement(
                        $targetPackage->getName(),
                        null
                    );
                    $package->addReplacement($replacement);
                }
            }

            // Add random optional dependencies
            if ($this->faker->boolean(30)) { // 30% chance to have optional dependencies
                $numOptionalDependencies = $this->faker->numberBetween(1, 3);
                for ($j = 0; $j < $numOptionalDependencies; $j++) {
                    /** @var Package $targetPackage */
                    $targetPackage = $this->faker->randomElement($packages);
                    $optionalDependency = new OptionalDependency(
                        $targetPackage->getName(),
                        null
                    );
                    $package->addOptionalDependency($optionalDependency);
                }
            }

            // Add random provisions
            if ($this->faker->boolean(15)) { // 15% chance to have provisions
                $numProvisions = $this->faker->numberBetween(1, 2);
                for ($j = 0; $j < $numProvisions; $j++) {
                    /** @var Package $targetPackage */
                    $targetPackage = $this->faker->randomElement($packages);
                    $provision = new Provision(
                        $targetPackage->getName(),
                        null
                    );
                    $package->addProvision($provision);
                }
            }

            // Add random make dependencies
            if ($this->faker->boolean(25)) { // 25% chance to have make dependencies
                $numMakeDependencies = $this->faker->numberBetween(1, 3);
                for ($j = 0; $j < $numMakeDependencies; $j++) {
                    /** @var Package $targetPackage */
                    $targetPackage = $this->faker->randomElement($packages);
                    $makeDependency = new MakeDependency(
                        $targetPackage->getName(),
                        null
                    );
                    $package->addMakeDependency($makeDependency);
                }
            }

            // Add random check dependencies
            if ($this->faker->boolean(25)) { // 25% chance to have check dependencies
                $numCheckDependencies = $this->faker->numberBetween(1, 3);
                for ($j = 0; $j < $numCheckDependencies; $j++) {
                    /** @var Package $targetPackage */
                    $targetPackage = $this->faker->randomElement($packages);
                    $checkDependency = new CheckDependency(
                        $targetPackage->getName(),
                        null
                    );
                    $package->addCheckDependency($checkDependency);
                }
            }

            $errors = $this->validator->validate($package);
            if (count($errors) > 0) {
                throw new \RuntimeException((string) $errors);
            }

            $manager->persist($package);
        }

        $manager->flush();

        $this->abstractRelationRepository->updateTargets();
        $manager->flush();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run(new ArrayInput(['app:index:packages']));
    }

    public function getDependencies(): array
    {
        return [
            RepositoryFixtures::class,
        ];
    }
}
