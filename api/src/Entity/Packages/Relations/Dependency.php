<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Dependency extends AbstractRelation
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Packages\Package", inversedBy="dependencies")
     */
    protected Package $source;
}
