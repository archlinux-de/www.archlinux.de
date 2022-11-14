<?php

namespace App\Entity\Packages;

class Version implements \Stringable
{
    private const VERSION_PATTERN = '/^([<=>]*)(?:([0-9]+):)?([^;:\/\s]+?)(?:-([0-9.]+))?$/';

    public function __construct(
        private readonly string $version,
        private readonly ?string $release = null,
        private readonly int $epoch = 0,
        private readonly VersionConstraint $constraint = VersionConstraint::ANY
    ) {
    }

    public static function isValidString(string $version): bool
    {
        return preg_match(self::VERSION_PATTERN, $version) === 1;
    }

    public static function createFromString(string $version): self
    {
        if (preg_match(self::VERSION_PATTERN, $version, $matches)) {
            return new self($matches[3], $matches[4] ?? null, (int)$matches[2], VersionConstraint::from($matches[1]));
        } else {
            throw new \RuntimeException(sprintf('Invalid version %s', $version));
        }
    }

    public function __toString(): string
    {
        return $this->toShortString();
    }

    public function toShortString(): string
    {
        return sprintf(
            '%s%s%s',
            ($this->epoch > 0 ? $this->epoch . ':' : ''),
            $this->version,
            ($this->release ? sprintf('-%s', $this->release) : '')
        );
    }

    public function toNormalizedString(): string
    {
        return sprintf(
            '%s:%s-%s',
            $this->epoch,
            $this->version,
            $this->release ?? 1
        );
    }

    public function getConstraint(): VersionConstraint
    {
        return $this->constraint;
    }

    public function getEpoch(): int
    {
        return $this->epoch;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getRelease(): ?string
    {
        return $this->release;
    }
}
