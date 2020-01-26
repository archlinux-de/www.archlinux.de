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
            ->setMd5sum($data['MD5SUM'])
            ->setPackager(Packager::createFromString($this->normalizeEmail($data['PACKAGER'])))
            ->setSha256sum($data['SHA256SUM'])
            ->setPgpSignature($data['PGPSIG'])
            ->setLicenses((array)($data['LICENSE'] ?? []))
            ->setGroups((array)($data['GROUPS'] ?? []))
            ->setFiles(Files::createFromArray((array)$data['FILES']));

        foreach ((array)($data['DEPENDS'] ?? []) as $depend) {
            $package->addDependency(Dependency::createFromString($depend));
        }
        foreach ((array)($data['CONFLICTS'] ?? []) as $conflict) {
            $package->addConflict(Conflict::createFromString($conflict));
        }
        foreach ((array)($data['REPLACES'] ?? []) as $replacement) {
            $package->addReplacement(Replacement::createFromString($replacement));
        }
        foreach ((array)($data['OPTDEPENDS'] ?? []) as $optDepend) {
            $package->addOptionalDependency(OptionalDependency::createFromString($optDepend));
        }
        foreach ((array)($data['PROVIDES'] ?? []) as $provide) {
            $package->addProvision(Provision::createFromString($provide));
        }
        foreach ((array)($data['MAKEDEPENDS'] ?? []) as $makeDepend) {
            $package->addMakeDependency(MakeDependency::createFromString($makeDepend));
        }
        foreach ((array)($data['CHECKDEPENDS'] ?? []) as $checkDepend) {
            $package->addCheckDependency(CheckDependency::createFromString($checkDepend));
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
            ->trim("\u{200e}")
        ;

        return $urlString->toString();
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
