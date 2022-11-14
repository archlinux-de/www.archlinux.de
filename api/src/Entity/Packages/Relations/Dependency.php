<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Dependency extends AbstractRelation implements LibraryRelation
{
    use LibraryRelationTrait;

    #[ORM\ManyToOne(targetEntity: Package::class, inversedBy: 'dependencies')]
    #[ORM\JoinColumn(nullable: false)]
    protected Package $source;
}
