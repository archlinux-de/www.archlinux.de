<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class OptionalDependency extends AbstractRelation
{
    #[ORM\ManyToOne(targetEntity: Package::class, inversedBy: 'optionalDependencies')]
    protected Package $source;
}
