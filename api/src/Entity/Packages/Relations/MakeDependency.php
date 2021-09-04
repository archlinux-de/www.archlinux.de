<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MakeDependency extends AbstractRelation
{
    #[ORM\ManyToOne(targetEntity: Package::class, inversedBy: 'makeDependencies')]
    protected Package $source;
}
