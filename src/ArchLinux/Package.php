<?php

namespace App\ArchLinux;

class Package
{
    /** @var \SplFileInfo */
    private $packageDir;

    /** @var \SplFileInfo */
    private $descFile;

    /** @var array<array<string>>|null */
    private $desc;

    /** @var array<string>|null */
    private $files;

    /**
     * @param \SplFileInfo $packageDir
     */
    public function __construct(\SplFileInfo $packageDir)
    {
        $this->packageDir = $packageDir;
        $this->descFile = new \SplFileInfo($packageDir->getPathname() . '/desc');
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->readValue('FILENAME');
    }

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    private function readValue(string $key, string $default = ''): string
    {
        $list = $this->readList($key);
        return $list[0] ?? $default;
    }

    /**
     * @param string $key
     * @param array<string> $default
     * @return array<string>
     */
    private function readList(string $key, array $default = []): array
    {
        if ($this->desc === null) {
            $this->desc = $this->loadInfo($this->descFile);
        }
        return $this->desc[$key] ?? $default;
    }

    /**
     * @param \SplFileInfo $descFile
     *
     * @return array<array<string>>
     */
    private function loadInfo(\SplFileInfo $descFile): array
    {
        $index = '';
        $data = array();
        $file = $descFile->openFile();
        $file->setFlags(\SplFileObject::READ_AHEAD | \SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY);

        foreach ($file as $line) {
            if (is_string($line)) {
                if (substr($line, 0, 1) == '%' && substr($line, -1) == '%') {
                    $index = substr($line, 1, -1);
                    $data[$index] = array();
                } else {
                    $data[$index][] = $line;
                }
            }
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getBase(): string
    {
        return $this->readValue('BASE', $this->getName());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->readValue('NAME');
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->readValue('VERSION');
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->readValue('DESC');
    }

    /**
     * @return array<string>
     */
    public function getGroups(): array
    {
        return $this->readList('GROUPS');
    }

    /**
     * @return int
     */
    public function getCompressedSize(): int
    {
        return (int)$this->readValue('CSIZE', '0');
    }

    /**
     * @return int
     */
    public function getInstalledSize(): int
    {
        return (int)$this->readValue('ISIZE', '0');
    }

    /**
     * @return string
     */
    public function getMd5sum(): string
    {
        return $this->readValue('MD5SUM');
    }

    /**
     * @return string|null
     */
    public function getSha256sum(): ?string
    {
        return $this->readValue('SHA256SUM') ?: null;
    }

    /**
     * @return string|null
     */
    public function getPgpSignature(): ?string
    {
        return $this->readValue('PGPSIG') ?: null;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->readValue('URL');
    }

    /**
     * @return array<string>
     */
    public function getLicenses(): array
    {
        return $this->readList('LICENSE');
    }

    /**
     * @return string
     */
    public function getArchitecture(): string
    {
        return $this->readValue('ARCH');
    }

    /**
     * @return \DateTime|null
     */
    public function getBuildDate(): ?\DateTime
    {
        $buildTimestamp = $this->readValue('BUILDDATE') ?: null;
        if ($buildTimestamp === null) {
            return null;
        }
        return (new \DateTime())->setTimestamp((int)$buildTimestamp);
    }

    /**
     * @return string
     */
    public function getPackager(): string
    {
        return $this->readValue('PACKAGER');
    }

    /**
     * @return array<string>
     */
    public function getReplaces(): array
    {
        return $this->readList('REPLACES');
    }

    /**
     * @return array<string>
     */
    public function getDepends(): array
    {
        return $this->readList('DEPENDS');
    }

    /**
     * @return array<string>
     */
    public function getConflicts(): array
    {
        return $this->readList('CONFLICTS');
    }

    /**
     * @return array<string>
     */
    public function getProvides(): array
    {
        return $this->readList('PROVIDES');
    }

    /**
     * @return array<string>
     */
    public function getOptDepends(): array
    {
        return $this->readList('OPTDEPENDS');
    }

    /**
     * @return array<string>
     */
    public function getMakeDepends(): array
    {
        return $this->readList('MAKEDEPENDS');
    }

    /**
     * @return array<string>
     */
    public function getCheckDepends(): array
    {
        return $this->readList('CHECKDEPENDS');
    }

    /**
     * @return array<string>
     */
    public function getFiles(): array
    {
        if ($this->files === null) {
            $this->files = $this->loadInfo(
                new \SplFileInfo($this->packageDir->getPathname() . '/files')
            )['FILES'];
        }

        return $this->files;
    }
}
