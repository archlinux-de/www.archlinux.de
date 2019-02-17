<?php

namespace App\Command\Dev;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
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

        $dateDirectories = $this->generateDateDirectories();
        $progressBar = new ProgressBar($output, count($dateDirectories));
        $progressBar->setFormat(
            "Updating to <info>%message%</info> [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%\n"
        );

        foreach ($dateDirectories as $dateDirectory) {
            $mirrorUrl = sprintf('https://archive.archlinux.org/repos/%s/', $dateDirectory);
            if ($this->checkMirrorUrl($mirrorUrl)) {
                $progressBar->setMessage($dateDirectory);
                $progressBar->advance();

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

        $progressBar->finish();

        return 0;
    }

    /**
     * @return array
     */
    private function generateDateDirectories(): array
    {
        $dates = [];
        foreach (range(((int)date('Y') - 1), date('Y')) as $year) {
            foreach (range(1, 12) as $month) {
                foreach (range(1, date('t', (int)strtotime($year . '-' . $month . '-1'))) as $day) {
                    if (date('Y') == $year) {
                        if ($month > date('n')) {
                            continue;
                        } elseif ($month == date('n')) {
                            if ($day > date('j')) {
                                continue;
                            }
                        }
                    }

                    $dates[] = sprintf(
                        '%d/%02d/%02d',
                        $year,
                        $month,
                        $day
                    );
                }
            }
        }

        return array_slice($dates, -366, 365);
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
