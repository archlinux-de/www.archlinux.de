<?php

namespace App\Repository;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AbstractRelationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
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

    /**
     * @param AbstractRelation $relation
     * @return Package|null
     */
    private function getBestPackageByRelation(AbstractRelation $relation): ?Package
    {
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
            /** @var Package $candidate */
            foreach ($candidates as $candidate) {
                if ($candidate->getRepository()->getId() == $relationRepository->getId()) {
                    return $candidate;
                }
            }
            /** @var Package $candidate */
            foreach ($candidates as $candidate) {
                $candidateRepository = $candidate->getRepository();
                if (
                    !$candidateRepository->isTesting()
                    && $candidateRepository->getArchitecture() == $relationRepository->getArchitecture()
                ) {
                    return $candidate;
                }
            }
        }

        return $this->getProviderByRelation($relation);
    }

    /**
     * @param AbstractRelation $relation
     * @return Package|null
     */
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
            if ($candidate->getSource()->getRepository()->getArchitecture() == $repositoryArchitecture) {
                $compatibleCandidates[] = $candidate->getSource();
            }
        }
        if (count($compatibleCandidates) == 1) {
            return $compatibleCandidates[0];
        }

        return null;
    }

    /**
     * @return AbstractRelation[]
     */
    public function findWithTargets(): array
    {
        return $this
            ->createQueryBuilder('relation')
            ->where('relation.target IS NOT NULL')
            ->getQuery()
            ->getResult();
    }
}
