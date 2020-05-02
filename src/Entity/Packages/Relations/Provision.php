<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Package;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Provision extends AbstractRelation
{
    /**
     * @var Package
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Packages\Package", inversedBy="provisions")
     */
    protected $source;
}
