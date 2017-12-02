<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class MakeDependency extends AbstractRelation
{
    /**
     * @var Package
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Packages\Package", inversedBy="makedepends")
     */
    protected $source;
}
