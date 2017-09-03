<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Packages\Architecture;
use AppBundle\Entity\Packages\Package;
use AppBundle\Entity\Packages\Relations\AbstractRelation;
use AppBundle\Entity\Packages\Relations\Dependency;
use AppBundle\Entity\Packages\Relations\OptionalDependency;
use AppBundle\Entity\Packages\Repository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class Packages extends Fixture
{
    /**
     * @param ObjectManager $manager
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function load(ObjectManager $manager)
    {
        $coreRepository = new Repository('core', Architecture::X86_64);
        $extraRepository = new Repository('extra', Architecture::X86_64);

        $glibc = new Package($coreRepository, 'glibc', '2.0-1', Architecture::X86_64);

        $pcre = new Package($coreRepository, 'pcre', '8.0-1', Architecture::X86_64);

        $pacman = new Package($coreRepository, 'pacman', '5.0-1', Architecture::X86_64);
        $pacman->addDependency(new Dependency($glibc->getName()));

        $php = new Package($extraRepository, 'php', '7.0-1', Architecture::X86_64);
        $php->addDependency(new Dependency($glibc->getName()));
        $php->addDependency(new Dependency($pcre->getName()));
        $php->addOptionalDependency(new OptionalDependency($pacman->getName()));

        $manager->persist($coreRepository);
        $manager->persist($extraRepository);

        $manager->persist($glibc);
        $manager->persist($pcre);
        $manager->persist($php);
        $manager->persist($pacman);

        $manager->flush();

        $this
            ->container
            ->get('doctrine.orm.entity_manager')
            ->getRepository(AbstractRelation::class)
            ->updateTargets();
    }
}
