<?php

namespace App\Entity\Packages\Relations;

interface LibraryRelation extends RelationTarget
{
    public function isLibrary(): bool;

    public function isLibraryArchitecture(string $architecture): bool;
}
