<?php

namespace App\Command\Dev;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * @codeCoverageIgnore
 */
class TestUpdatePackagesCommand extends Command
{
    /** @var ClientInterface */
    private $guzzleClient;

    /**
     * @param ClientInterface $guzzleClient
     */
    public function __construct(ClientInterface $guzzleClient)
    {
        parent::__construct();
        $this->guzzleClient = $guzzleClient;
    }

    protected function configure(): void
    {
        $this->setName('app:dev:test-update-packages');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $clearCache = new Process(['bin/console', 'cache:clear']);
        $clearCache->mustRun();

        $clearAppCache = new Process(['bin/console', 'cache:pool:clear', 'cache.app']);
        $clearAppCache->mustRun();

        $resetDatabase = new Process(['bin/console', 'app:reset:database', '--packages']);
        $resetDatabase->mustRun();

        $updateRepositories = new Process(['bin/console', 'app:update:repositories']);
        $updateRepositories->mustRun();

        foreach (range(2017, 2018) as $year) {
            foreach (range(1, 12) as $month) {
                foreach (range(1, 31) as $day) {
                    $mirrorUrl = sprintf('https://archive.archlinux.org/repos/%d/%02d/%02d/', $year, $month, $day);
                    if ($this->checkMirrorUrl($mirrorUrl)) {
                        $output->writeln(sprintf('Updating to %d-%02d-%02d', $year, $month, $day));
                        $updatePackages = new Process(
                            ['bin/console', 'app:update:packages'],
                            null,
                            ['PACKAGES_MIRROR' => $mirrorUrl]
                        );
                        $updatePackages->setTimeout(120);
                        $updatePackages->mustRun();
                        if (!$updatePackages->isSuccessful()) {
                            $output->writeln($updatePackages->getOutput());
                            return 1;
                        } else {
                            $output->writeln($updatePackages->getOutput());
                        }

                        $validatePackages = new Process(
                            ['bin/console', 'app:validate:packages'],
                            null,
                            ['PACKAGES_MIRROR' => $mirrorUrl]
                        );
                        $validatePackages->mustRun();
                        if (!$validatePackages->isSuccessful()) {
                            $output->writeln($validatePackages->getOutput());
                            return 1;
                        }
                    }
                }
            }
        }

        return 0;
    }

    /**
     * @param string $url
     * @return bool
     */
    private function checkMirrorUrl(string $url): bool
    {
        try {
            $content = $this->guzzleClient->request(
                'GET',
                $url . 'lastupdate'
            )->getBody()->getContents();
            return $content > 1;
        } catch (GuzzleException $e) {
            return false;
        }
    }
}
