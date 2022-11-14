<?php

namespace App\Entity\Packages\Relations;

use App\Entity\Packages\Architecture;

trait LibraryRelationTrait
{
    public function isLibrary(): bool
    {
        return str_ends_with($this->getTargetName(), '.so');
    }

    private function isTargetVersion(string $versionSuffix): bool
    {
        return str_ends_with($this->getTargetVersion() ?? '', $versionSuffix);
    }

    public function isLibraryArchitecture(string $architecture): bool
    {
        return match ($architecture) {
            Architecture::X86_64 => $this->isTargetVersion('-64'),
            Architecture::I686 => $this->isTargetVersion('-32'),
            default => false
        };
    }
}
