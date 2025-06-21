<?php

namespace App\Tests\Entity\Packages\Relations;

use App\Entity\Packages\Relations\LibraryRelation;
use App\Entity\Packages\Relations\LibraryRelationTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LibraryRelationTraitTest extends TestCase
{
    #[DataProvider('provideLibrary')]
    public function testIsLibrary(string $targetName, bool $isLibrary): void
    {
        $this->assertSame($isLibrary, (
        new readonly class ($targetName) implements LibraryRelation {
            use LibraryRelationTrait;

            public function __construct(private string $targetName)
            {
            }

            public function getTargetName(): string
            {
                return $this->targetName;
            }

            public function getTargetVersion(): ?string // @phpstan-ignore return.unusedType
            {
                return '';
            }
        })->isLibrary());
    }

    /**
     * @return iterable<array<string|boolean>>
     */
    public static function provideLibrary(): iterable
    {
        return [
            ['lib.so', true],
            ['', false],
            ['app', false],
            ['appso', false],
            ['lib.so-32', false]
        ];
    }

    #[DataProvider('provideLibraryArchitecture')]
    public function testIsLibraryArchitecture(
        string $targetVersion,
        string $architecture,
        bool $isArchitecture
    ): void {
        $this->assertSame($isArchitecture, (
        new readonly class ($targetVersion) implements LibraryRelation {
            use LibraryRelationTrait;

            public function __construct(private string $targetVersion)
            {
            }

            public function getTargetName(): string
            {
                return 'lib.so';
            }

            public function getTargetVersion(): ?string // @phpstan-ignore return.unusedType
            {
                return $this->targetVersion;
            }
        })->isLibraryArchitecture($architecture));
    }

    /**
     * @return iterable<array<string|boolean>>
     */
    public static function provideLibraryArchitecture(): iterable
    {
        return [
            ['1.2.3-64', 'x86_64', true],
            ['1.2.3-32', 'i686', true],
            ['1.2.3-16', 'i686', false],
            ['1.2.3', 'x86_64', false]
        ];
    }
}
