<?php

namespace App\Repository;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;

class AbstractRelationRepository extends EntityRepository
{
    public function updateTargets()
    {
        /** @var AbstractRelation $relation */
        foreach ($this->findAll() as $relation) {
            $relation->setTarget($this->getBestPackageByRelation($relation));
            $this->getEntityManager()->persist($relation);
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
                if (!$candidateRepository->isTesting()
                    && $candidateRepository->getArchitecture() == $relationRepository->getArchitecture()) {
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
        /** @var AbstractRelation $candidate */
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
}
