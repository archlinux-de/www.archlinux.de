<?php

namespace App\Repository;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbstractRelation>
 */
class AbstractRelationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractRelation::class);
    }

    public function updateTargets(): void
    {
        /** @var AbstractRelation $relation */
        foreach ($this->findAll() as $relation) {
            $relation->setTarget($this->getBestPackageByRelation($relation));
        }
    }

    private function getBestPackageByRelation(AbstractRelation $relation): ?Package
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

        if (count($candidates) > 0) {
            $relationRepository = $relation->getSource()->getRepository();

            foreach ($candidates as $candidate) {
                if ($candidate->getRepository()->getId() == $relationRepository->getId()) {
                    if ($this->isVersionCompatible($candidate->getVersion(), $relation->getTargetVersion())) {
                        return $candidate;
                    }
                }
            }

            foreach ($candidates as $candidate) {
                $candidateRepository = $candidate->getRepository();
                if (
                    !$candidateRepository->isTesting()
                    && $candidateRepository->getArchitecture() == $relationRepository->getArchitecture()
                ) {
                    if ($this->isVersionCompatible($candidate->getVersion(), $relation->getTargetVersion())) {
                        return $candidate;
                    }
                }
            }
        }

        return $this->getProviderByRelation($relation);
    }

    private function isVersionCompatible(?string $providedVersion, ?string $requestedVersion): bool
    {
        if (!$requestedVersion) {
            return true;
        }

        if (!$providedVersion) {
            return false;
        }

        $providedVersion = (string)preg_replace('/^=(.*)$/', '$1', $providedVersion);

        if (preg_match('/^([<>=]+)(.+)$/', $requestedVersion, $matches)) {
            return version_compare($providedVersion, $matches[2], $matches[1]);
        }

        return false;
    }

    private function getProviderByRelation(AbstractRelation $relation): ?Package
    {
        $repositoryArchitecture = $relation->getSource()->getRepository()->getArchitecture();
        /** @var AbstractRelation[] $candidates */
        $candidates = $this->createQueryBuilder('relation')
            ->where('relation INSTANCE OF App:Packages\Relations\Provision')
            ->andWhere('relation.targetName = :targetName')
            ->setParameter('targetName', $relation->getTargetName())
            ->getQuery()
            ->getResult();
        $compatibleCandidates = [];

        foreach ($candidates as $candidate) {
            $candidateSource = $candidate->getSource();

            if ($candidateSource->getRepository()->getArchitecture() !== $repositoryArchitecture) {
                continue;
            }

            if ($relation->getTargetVersion()) {
                foreach ($candidateSource->getProvisions() as $compatibleCandidateProvision) {
                    if (
                        $this->isVersionCompatible(
                            $compatibleCandidateProvision->getTargetVersion(),
                            $relation->getTargetVersion()
                        )
                    ) {
                        if (!isset($compatibleCandidates[$candidateSource->getId()])) {
                            $compatibleCandidates[$candidateSource->getId()] = $candidateSource;
                        }
                    }
                }
            } else {
                $compatibleCandidates[$candidateSource->getId()] = $candidateSource;
            }
        }

        if (count($compatibleCandidates) == 1) {
            return array_values($compatibleCandidates)[0];
        }

        return null;
    }
}
