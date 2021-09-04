<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Conflict extends AbstractRelation
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Packages\Package", inversedBy="conflicts")
     */
    protected Package $source;
}
