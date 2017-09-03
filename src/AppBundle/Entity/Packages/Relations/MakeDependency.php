<?php

namespace AppBundle\Entity\Packages\Relations;

use AppBundle\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class MakeDependency extends AbstractRelation
{
    /**
     * @var Package
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Packages\Package", inversedBy="makedepends")
     */
    protected $source;
}
