<?php

namespace App\Repository;

use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;

class AbstractRelationRepository extends EntityRepository
{
    /**
     * @throws ORMException
     */
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
        try {
            return $this->createQueryBuilder('relation')
                ->where('relation INSTANCE OF App:Packages\Relations\Provision')
                ->andWhere('relation.targetName = :target')
                ->setParameter('target', $relation->getTargetName())
                ->getQuery()
                ->getSingleResult()
                ->getSource();
        } catch (NonUniqueResultException $e) {
            return null;
        } catch (NoResultException $e) {
            return null;
        }
    }
}
