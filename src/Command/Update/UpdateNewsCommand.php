<?php

namespace App\Command\Update;

use App\Entity\NewsItem;
use App\Repository\NewsItemRepository;
use App\Service\NewsItemFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateNewsCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var NewsItemFetcher */
    private $newsItemFetcher;

    /** @var NewsItemRepository */
    private $newsItemRepository;

    /** @var ValidatorInterface */
    private $validator;

    /**
     * @param EntityManagerInterface $entityManager
     * @param NewsItemFetcher $newsItemFetcher
     * @param NewsItemRepository $newsItemRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        NewsItemFetcher $newsItemFetcher,
        NewsItemRepository $newsItemRepository,
        ValidatorInterface $validator
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->newsItemFetcher = $newsItemFetcher;
        $this->newsItemRepository = $newsItemRepository;
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this->setName('app:update:news');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);

        $ids = [];
        $oldestLastModified = new \DateTime();
        /** @var NewsItem $newsItem */
        foreach ($this->newsItemFetcher as $newsItem) {
            $errors = $this->validator->validate($newsItem);
            if ($errors->count() > 0) {
                throw new \RuntimeException((string)json_encode($errors));
            }

            $this->entityManager->merge($newsItem);
            $ids[] = $newsItem->getId();
            if ($oldestLastModified > $newsItem->getLastModified()) {
                $oldestLastModified = $newsItem->getLastModified();
            }
        }
        foreach ($this->newsItemRepository->findAllExceptByIdsNewerThan($ids, $oldestLastModified) as $newsItem) {
            $this->entityManager->remove($newsItem);
        }

        $this->entityManager->flush();
        $this->release();
    }
}
