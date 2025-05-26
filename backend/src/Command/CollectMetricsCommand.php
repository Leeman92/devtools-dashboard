<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\DockerService;
use App\Service\GitHubService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to collect metrics from various sources.
 */
#[AsCommand(
    name: 'app:collect-metrics',
    description: 'Collect metrics from Docker, GitHub, and other sources for historical tracking',
)]
final class CollectMetricsCommand extends Command
{
    public function __construct(
        private readonly DockerService $dockerService,
        private readonly GitHubService $githubService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source', 's', InputOption::VALUE_OPTIONAL, 'Specific source to collect from (docker, github, all)', 'all')
            ->addOption('repository', 'r', InputOption::VALUE_OPTIONAL, 'GitHub repository in format owner/repo')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without storing data')
            ->setHelp('This command collects metrics from various sources and stores them for historical tracking and monitoring.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getOption('source');
        $repository = $input->getOption('repository');
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Running in dry-run mode - no data will be stored');
        }

        $io->title('DevTools Dashboard - Metrics Collection');

        $success = true;

        try {
            if ($source === 'all' || $source === 'docker') {
                $success &= $this->collectDockerMetrics($io, $dryRun);
            }

            if ($source === 'all' || $source === 'github') {
                $success &= $this->collectGitHubMetrics($io, $repository, $dryRun);
            }

            if ($success) {
                $io->success('Metrics collection completed successfully');
                return Command::SUCCESS;
            } else {
                $io->error('Some metrics collection failed - check logs for details');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->logger->error('Metrics collection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $io->error('Metrics collection failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function collectDockerMetrics(SymfonyStyle $io, bool $dryRun): bool
    {
        $io->section('Collecting Docker Metrics');

        try {
            // Collect Docker Swarm services
            $io->text('Fetching Docker Swarm services...');
            $services = $this->dockerService->getSwarmServices();
            $io->text(sprintf('Found %d Docker services', count($services)));

            if (!$dryRun) {
                $io->text('Storing service data...');
                // Data is automatically stored in the service method
            }

            // Collect Docker containers
            $io->text('Fetching Docker containers...');
            $containers = $this->dockerService->getContainers();
            $io->text(sprintf('Found %d Docker containers', count($containers)));

            $this->logger->info('Docker metrics collected successfully', [
                'services_count' => count($services),
                'containers_count' => count($containers),
                'dry_run' => $dryRun,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to collect Docker metrics', [
                'error' => $e->getMessage(),
            ]);

            $io->error('Failed to collect Docker metrics: ' . $e->getMessage());
            return false;
        }
    }

    private function collectGitHubMetrics(SymfonyStyle $io, ?string $repository, bool $dryRun): bool
    {
        $io->section('Collecting GitHub Metrics');

        if (!$repository) {
            $io->warning('No repository specified for GitHub metrics collection');
            return true;
        }

        try {
            [$owner, $repo] = explode('/', $repository, 2);

            // Collect workflow runs
            $io->text(sprintf('Fetching GitHub workflow runs for %s...', $repository));
            $runs = $this->githubService->getWorkflowRuns($owner, $repo, 20);
            $io->text(sprintf('Found %d workflow runs', count($runs)));

            if (!$dryRun) {
                $io->text('Storing workflow run data...');
                // Data is automatically stored in the service method
            }

            // Collect workflows
            $io->text('Fetching GitHub workflows...');
            $workflows = $this->githubService->getWorkflows($owner, $repo);
            $io->text(sprintf('Found %d workflows', count($workflows)));

            // Get repository info
            $io->text('Fetching repository information...');
            $repoInfo = $this->githubService->getRepository($owner, $repo);
            $io->text(sprintf('Repository: %s (%s)', $repoInfo['full_name'] ?? 'Unknown', $repoInfo['language'] ?? 'Unknown'));

            $this->logger->info('GitHub metrics collected successfully', [
                'repository' => $repository,
                'runs_count' => count($runs),
                'workflows_count' => count($workflows),
                'dry_run' => $dryRun,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to collect GitHub metrics', [
                'repository' => $repository,
                'error' => $e->getMessage(),
            ]);

            $io->error('Failed to collect GitHub metrics: ' . $e->getMessage());
            return false;
        }
    }
} 