<?php

namespace App\Repository;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Relations\LibraryRelation;
use App\Entity\Packages\Version;
use App\Service\PackageVersionCompare;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbstractRelation>
 */
class AbstractRelationRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly PackageVersionCompare $packageVersionCompare
    ) {
        parent::__construct($registry, AbstractRelation::class);
    }

    public function updateTargets(): void
    {
        /** @var AbstractRelation $relation */
        foreach ($this->findAll() as $relation) {
            $relation->setTarget($this->getBestPackageByRelation($relation));
        }
    }

    /**
     * @return Package[]
     */
    public function getPackagesByRelation(AbstractRelation $relation): array
    {
        /** @var Package[] $candidates */
        $candidates = $this
            ->getEntityManager()
            ->getRepository(Package::class)
            ->createQueryBuilder('target')
            ->where('target.name = :name')
            ->setParameter('name', $relation->getTargetName())
            ->getQuery()
            ->getResult();

        return $candidates;
    }

    /**
     * @return AbstractRelation[]
     */
    public function getProvidersByRelation(AbstractRelation $relation): array
    {
        /** @var AbstractRelation[] $candidates */
        $candidates = $this->createQueryBuilder('relation')
            ->where('relation INSTANCE OF App:Packages\Relations\Provision')
            ->andWhere('relation.targetName = :targetName')
            ->setParameter('targetName', $relation->getTargetName())
            ->getQuery()
            ->getResult();

        return $candidates;
    }

    private function getBestPackageByRelation(AbstractRelation $relation): ?Package
    {
        $candidates = $this->getPackagesByRelation($relation);

        if (count($candidates) > 0) {
            foreach ($candidates as $candidate) {
                if (
                    $candidate->getRepository()->isTesting()
                    && !$relation->getSource()->getRepository()->isTesting()
                ) {
                    continue;
                }

                if ($this->isVersionCompatible($candidate->getVersion(), $relation->getTargetVersion())) {
                    return $candidate;
                }
            }
        }

        return $this->getProviderByRelation($relation);
    }

    private function isVersionCompatible(?string $providedVersionString, ?string $requestedVersionString): bool
    {
        if (!$requestedVersionString) {
            return true;
        }

        if (!$providedVersionString) {
            return false;
        }

        $providedVersion = Version::createFromString($providedVersionString);
        $requestedVersion = Version::createFromString($requestedVersionString);

        return $this->packageVersionCompare->satisfies($providedVersion, $requestedVersion);
    }

    private function isCompatiblePackage(
        AbstractRelation $relation,
        Package $candidateSource
    ): bool {
        foreach ($candidateSource->getProvisions() as $compatibleCandidateProvision) {
            if (
                $this->isVersionCompatible(
                    $compatibleCandidateProvision->getTargetVersion(),
                    $relation->getTargetVersion()
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function isCompatibleLibrary(
        AbstractRelation $relation,
        Package $candidateSource,
        string $repositoryArchitecture
    ): bool {
        if ($relation instanceof LibraryRelation && $relation->isLibrary()) {
            foreach ($candidateSource->getProvisions() as $compatibleCandidateProvision) {
                if (
                    $compatibleCandidateProvision instanceof LibraryRelation
                    && $compatibleCandidateProvision->isLibrary()
                ) {
                    switch ($repositoryArchitecture) {
                        case Architecture::X86_64:
                        case Architecture::I686:
                            if (
                                !$compatibleCandidateProvision
                                    ->isLibraryArchitecture($repositoryArchitecture)
                            ) {
                                return false;
                            }
                    }
                }
            }
        }

        return true;
    }

    private function getProviderByRelation(AbstractRelation $relation): ?Package
    {
        $repositoryArchitecture = $relation->getSource()->getRepository()->getArchitecture();
        $candidates = $this->getProvidersByRelation($relation);
        $compatibleCandidates = [];

        foreach ($candidates as $candidate) {
            $candidateSource = $candidate->getSource();

            if ($relation->getSource()->getId() === $candidateSource->getId()) {
                return $candidateSource;
            }

            if ($candidateSource->getRepository()->getArchitecture() !== $repositoryArchitecture) {
                continue;
            }

            if (
                $candidateSource->getRepository()->isTesting()
                && !$relation->getSource()->getRepository()->isTesting()
            ) {
                continue;
            }

            if (!$this->isCompatiblePackage($relation, $candidateSource)) {
                continue;
            }

            if (!$this->isCompatibleLibrary($relation, $candidateSource, $repositoryArchitecture)) {
                continue;
            }

            if (!isset($compatibleCandidates[$candidateSource->getId()])) {
                $compatibleCandidates[$candidateSource->getId()] = $candidateSource;
            }
        }

        if (count($compatibleCandidates) == 1) {
            return array_values($compatibleCandidates)[0];
        }

        return null;
    }
}
