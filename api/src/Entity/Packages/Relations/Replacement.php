<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Replacement extends AbstractRelation
{
    #[ORM\ManyToOne(targetEntity: Package::class, inversedBy: 'replacements')]
    protected Package $source;
}
