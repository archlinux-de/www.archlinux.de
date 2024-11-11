<?php

namespace App\Serializer;

use App\Entity\Packages\Files;
use App\Entity\Packages\Package;
use App\Entity\Packages\Packager;
use App\Entity\Packages\Relations\CheckDependency;
use App\Entity\Packages\Relations\Conflict;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\MakeDependency;
use App\Entity\Packages\Relations\OptionalDependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Relations\Replacement;
use App\Entity\Packages\Repository;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\String\ByteString;

class PackageDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Package
    {
        assert(is_array($data));
        assert($context['repository'] instanceof Repository);

        $package = (new Package(
            $context['repository'],
            $data['NAME'],
            $data['VERSION'],
            $data['ARCH']
        ))
            ->setFileName($data['FILENAME'])
            ->setUrl($this->normalizeUrl($data['URL'] ?? null))
            ->setDescription($data['DESC'])
            ->setBase($data['BASE'] ?? $data['NAME'])
            ->setBuildDate((new \DateTime())->setTimestamp($data['BUILDDATE']))
            ->setCompressedSize($data['CSIZE'])
            ->setInstalledSize($data['ISIZE'])
            ->setPackager($this->createPackagerFromString($data['PACKAGER']))
            ->setSha256sum($data['SHA256SUM'])
            ->setLicenses($this->createLicenseArray((array)($data['LICENSE'] ?? [])))
            ->setGroups((array)($data['GROUPS'] ?? []))
            ->setFiles(Files::createFromArray((array)($data['FILES'] ?? [])));

        foreach ((array)($data['DEPENDS'] ?? []) as $depend) {
            $package->addDependency($this->createDependency($depend));
        }
        foreach ((array)($data['CONFLICTS'] ?? []) as $conflict) {
            $package->addConflict($this->createConflict($conflict));
        }
        foreach ((array)($data['REPLACES'] ?? []) as $replacement) {
            $package->addReplacement($this->createReplacement($replacement));
        }
        foreach ((array)($data['OPTDEPENDS'] ?? []) as $optDepend) {
            $package->addOptionalDependency($this->createOptionalDependency($optDepend));
        }
        foreach ((array)($data['PROVIDES'] ?? []) as $provide) {
            $package->addProvision($this->createProvision($provide));
        }
        foreach ((array)($data['MAKEDEPENDS'] ?? []) as $makeDepend) {
            $package->addMakeDependency($this->createMakeDependency($makeDepend));
        }
        foreach ((array)($data['CHECKDEPENDS'] ?? []) as $checkDepend) {
            $package->addCheckDependency($this->createCheckDependency($checkDepend));
        }

        return $package;
    }

    private function normalizeUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $urlString = new ByteString($url);
        $urlString = $urlString
            ->replaceMatches('#^git://github.com/(.+).git$#', 'https://github.com/$1');

        return $urlString->toString();
    }

    private function createPackagerFromString(?string $packagerDefinition): ?Packager
    {
        if (!$packagerDefinition) {
            return null;
        }

        preg_match('/([^<>]+)(?:<(.+?)>)?/', $packagerDefinition, $matches);
        $name = trim($matches[1] ?? $packagerDefinition);
        $email = !empty($matches[2]) ? trim($matches[2]) : null;

        if (!$name && !$email) {
            return null;
        }

        return new Packager($name, $email);
    }

    private function createDependency(string $targetDefinition): Dependency
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new Dependency($target['name'], $target['version']);
    }

    private function createTargetFromString(string $targetDefinition): array
    {
        if (preg_match('/^([\w\-+@.]+?)((?:<|<=|=|>=|>)+[\w.:\-]+)(?::.+)?$/', $targetDefinition, $matches) > 0) {
            $targetName = $matches[1];
            $targetVersion = $matches[2];
        } elseif (preg_match('/^([\w\-+@.]+)/', $targetDefinition, $matches) > 0) {
            $targetName = $matches[1];
            $targetVersion = null;
        } else {
            $targetName = $targetDefinition;
            $targetVersion = null;
        }
        return ['name' => $targetName, 'version' => $targetVersion];
    }

    private function createConflict(string $targetDefinition): Conflict
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new Conflict($target['name'], $target['version']);
    }

    private function createReplacement(string $targetDefinition): Replacement
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new Replacement($target['name'], $target['version']);
    }

    private function createOptionalDependency(string $targetDefinition): OptionalDependency
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new OptionalDependency($target['name'], $target['version']);
    }

    private function createProvision(string $targetDefinition): Provision
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new Provision($target['name'], $target['version']);
    }

    private function createMakeDependency(string $targetDefinition): MakeDependency
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new MakeDependency($target['name'], $target['version']);
    }

    private function createCheckDependency(string $targetDefinition): CheckDependency
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new CheckDependency($target['name'], $target['version']);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === Package::class
            && $format === 'pacman-database'
            && isset($context['repository'])
            && $context['repository'] instanceof Repository;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Package::class => false];
    }

    /**
     * @param string[] $licenses
     * @return string[]
     */
    private function createLicenseArray(array $licenses): array
    {
        $result = [];
        foreach ($licenses as $licenseString) {
            foreach (preg_split('/\s*and\s*/i', $licenseString) ?: [] as $license) {
                $result[] = trim($license, " \t\n\r\0\x0B()");
            }
        }

        return $result;
    }
}
