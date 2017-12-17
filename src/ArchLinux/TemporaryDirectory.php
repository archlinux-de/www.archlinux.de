<?php

namespace App\ArchLinux;

use Symfony\Component\Process\Process;

class TemporaryDirectory extends \FilesystemIterator
{
    /** @var string */
    private $directory;

    public function __construct()
    {
        $mktemp = new Process(['mktemp', '-d']);
        $mktemp->mustRun();
        $this->directory = trim($mktemp->getOutput());

        parent::__construct($this->directory);
    }

    public function __destruct()
    {
        $rmdir = new Process(['rm', '-rf', $this->directory]);
        $rmdir->mustRun();
    }
}
