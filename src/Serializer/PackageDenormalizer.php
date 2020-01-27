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
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\String\ByteString;

class PackageDenormalizer implements ContextAwareDenormalizerInterface
{
    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<mixed> $context
     * @return Package
     * @throws \Exception
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): Package
    {
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
            ->setPackager($this->createPackagerFromString($this->normalizeEmail($data['PACKAGER'])))
            ->setSha256sum($data['SHA256SUM'])
            ->setPgpSignature($data['PGPSIG'])
            ->setLicenses((array)($data['LICENSE'] ?? []))
            ->setGroups((array)($data['GROUPS'] ?? []))
            ->setFiles(Files::createFromArray((array)$data['FILES']));

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

    /**
     * @param string|null $url
     * @return string|null
     */
    private function normalizeUrl(?string $url): ?string
    {
        if ($url == null) {
            return null;
        }
        $urlString = new ByteString($url);

        if (!$urlString->startsWith(['http://', 'https://', 'ftp://'])) {
            $urlString = $urlString->prepend('http://');
        }
        $urlString = $urlString
            ->replace(' ', '%20')
            ->trim("\u{200e}");

        return $urlString->toString();
    }

    /**
     * @param string $packagerDefinition
     * @return Packager
     */
    private function createPackagerFromString(string $packagerDefinition): Packager
    {
        preg_match('/([^<>]+)(?:<(.+?)>)?/', $packagerDefinition, $matches);
        $name = trim($matches[1] ?? $packagerDefinition);
        $email = trim($matches[2] ?? '');

        return new Packager($name, $email);
    }

    /**
     * @param string $email
     * @return string
     */
    private function normalizeEmail(string $email): string
    {
        return (new ByteString($email))
            ->replaceMatches('/(\s+dot\s+)/i', '.')
            ->replaceMatches('/(\s+at\s+)/i', '@')
            ->replaceMatches('/gmail-com:\s*(\S+)/i', '$1@gmail.com')
            ->toString();
    }

    /**
     * @param string $targetDefinition
     * @return Dependency
     */
    private function createDependency(string $targetDefinition): Dependency
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new Dependency($target['name'], $target['version']);
    }

    /**
     * @param string $targetDefinition
     * @return array<string>
     */
    private function createTargetFromString(string $targetDefinition): array
    {
        if (preg_match('/^([\w\-+@.]+?)((?:<|<=|=|>=|>)+[\w.:]+)/', $targetDefinition, $matches) > 0) {
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

    /**
     * @param string $targetDefinition
     * @return Conflict
     */
    private function createConflict(string $targetDefinition): Conflict
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new Conflict($target['name'], $target['version']);
    }

    /**
     * @param string $targetDefinition
     * @return Replacement
     */
    private function createReplacement(string $targetDefinition): Replacement
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new Replacement($target['name'], $target['version']);
    }

    /**
     * @param string $targetDefinition
     * @return OptionalDependency
     */
    private function createOptionalDependency(string $targetDefinition): OptionalDependency
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new OptionalDependency($target['name'], $target['version']);
    }

    /**
     * @param string $targetDefinition
     * @return Provision
     */
    private function createProvision(string $targetDefinition): Provision
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new Provision($target['name'], $target['version']);
    }

    /**
     * @param string $targetDefinition
     * @return MakeDependency
     */
    private function createMakeDependency(string $targetDefinition): MakeDependency
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new MakeDependency($target['name'], $target['version']);
    }

    /**
     * @param string $targetDefinition
     * @return CheckDependency
     */
    private function createCheckDependency(string $targetDefinition): CheckDependency
    {
        $target = $this->createTargetFromString($targetDefinition);
        return new CheckDependency($target['name'], $target['version']);
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<mixed> $context
     * @return bool
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return $type == Package::class
            && $format == 'pacman-database'
            && isset($context['repository'])
            && $context['repository'] instanceof Repository;
    }
}
