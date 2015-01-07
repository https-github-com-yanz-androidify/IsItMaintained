<?php

namespace Maintained\Application\Command;

use BlackBox\MapStorage;
use Maintained\Repository;
use Maintained\Statistics\StatisticsProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

/**
 * CLI command to update the cached statistics of a repository.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class UpdateStatisticsCommand extends Command
{
    /**
     * @var MapStorage
     */
    private $repositoryStorage;

    /**
     * @var MapStorage
     */
    private $statisticsCache;

    /**
     * @var StatisticsProvider
     */
    private $statisticsProvider;

    public function __construct(
        MapStorage $repositoryStorage,
        MapStorage $statisticsCache,
        StatisticsProvider $statisticsProvider
    ) {
        $this->repositoryStorage = $repositoryStorage;
        $this->statisticsCache = $statisticsCache;
        $this->statisticsProvider = $statisticsProvider;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('stats:update')
            ->setDescription('Updates the cached statistics');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = new LockHandler($this->getName());
        if (! $lock->lock()) {
            $output->writeln('The command is already running in another process.');
            return 0;
        }

        /** @var Repository[] $repositories */
        $repositories = iterator_to_array($this->repositoryStorage);

        usort($repositories, function (Repository $a, Repository $b) {
            return $a->getLastUpdateTimestamp() - $b->getLastUpdateTimestamp();
        });

        foreach ($repositories as $repository) {
            $output->writeln(sprintf('Updating <info>%s</info>', $repository->getName()));

            $timer = microtime(true);

            $this->update($repository);

            $output->writeln(sprintf('Took %ds', microtime(true) - $timer));

            // Updates only 1 at a time for now
            break;
        }

        $lock->release();
        return 0;
    }

    private function update(Repository $repository)
    {
        // Clear the cache
        $this->statisticsCache->set($repository->getName(), null);

        // Warmup the cache
        list($user, $repositoryName) = explode('/', $repository->getName(), 2);
        $this->statisticsProvider->getStatistics($user, $repositoryName);

        // Mark the repository updated
        $repository->update();
        $this->repositoryStorage->set($repository->getName(), $repository);
    }
}
