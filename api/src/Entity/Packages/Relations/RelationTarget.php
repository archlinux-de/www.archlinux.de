<?php

namespace App\Entity\Packages\Relations;

interface RelationTarget
{
    public function getTargetName(): string;

    public function getTargetVersion(): ?string;
}
