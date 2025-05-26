<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CicdPipeline;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * GitHub Service for interacting with GitHub API to monitor CI/CD pipelines.
 */
final readonly class GitHubService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private string $githubToken,
        private string $githubApiUrl = 'https://api.github.com',
    ) {}

    /**
     * Get workflow runs for a repository.
     *
     * @return array<string, mixed>
     */
    public function getWorkflowRuns(string $owner, string $repo, int $limit = 10): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->githubApiUrl}/repos/{$owner}/{$repo}/actions/runs", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->githubToken,
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'DevTools-Dashboard/1.0',
                ],
                'query' => [
                    'per_page' => $limit,
                    'page' => 1,
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();
            $runs = $data['workflow_runs'] ?? [];
            $result = [];

            foreach ($runs as $run) {
                $runData = $this->parseWorkflowRun($run, "{$owner}/{$repo}");
                $result[] = $runData;
                
                // Store historical data
                $this->storeWorkflowRun($runData);
            }

            $this->logger->info('Retrieved GitHub workflow runs', [
                'repository' => "{$owner}/{$repo}",
                'run_count' => count($result),
            ]);

            return $result;

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to retrieve GitHub workflow runs', [
                'repository' => "{$owner}/{$repo}",
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get all workflows for a repository.
     *
     * @return array<string, mixed>
     */
    public function getWorkflows(string $owner, string $repo): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->githubApiUrl}/repos/{$owner}/{$repo}/actions/workflows", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->githubToken,
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'DevTools-Dashboard/1.0',
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();
            $workflows = $data['workflows'] ?? [];

            $this->logger->info('Retrieved GitHub workflows', [
                'repository' => "{$owner}/{$repo}",
                'workflow_count' => count($workflows),
            ]);

            return array_map([$this, 'parseWorkflow'], $workflows);

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to retrieve GitHub workflows', [
                'repository' => "{$owner}/{$repo}",
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get repository information.
     *
     * @return array<string, mixed>
     */
    public function getRepository(string $owner, string $repo): array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->githubApiUrl}/repos/{$owner}/{$repo}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->githubToken,
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'DevTools-Dashboard/1.0',
                ],
                'timeout' => 10,
            ]);

            $repo = $response->toArray();

            return [
                'id' => $repo['id'],
                'name' => $repo['name'],
                'full_name' => $repo['full_name'],
                'description' => $repo['description'],
                'private' => $repo['private'],
                'default_branch' => $repo['default_branch'],
                'language' => $repo['language'],
                'size' => $repo['size'],
                'stargazers_count' => $repo['stargazers_count'],
                'watchers_count' => $repo['watchers_count'],
                'forks_count' => $repo['forks_count'],
                'open_issues_count' => $repo['open_issues_count'],
                'created_at' => $repo['created_at'],
                'updated_at' => $repo['updated_at'],
                'pushed_at' => $repo['pushed_at'],
                'html_url' => $repo['html_url'],
            ];

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to retrieve GitHub repository', [
                'repository' => "{$owner}/{$repo}",
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get historical pipeline data.
     *
     * @return CicdPipeline[]
     */
    public function getPipelineHistory(string $repository, int $hours = 24): array
    {
        $since = new \DateTimeImmutable("-{$hours} hours");

        return $this->entityManager
            ->getRepository(CicdPipeline::class)
            ->createQueryBuilder('cp')
            ->where('cp.repository = :repository')
            ->andWhere('cp.startedAt >= :since')
            ->setParameter('repository', $repository)
            ->setParameter('since', $since)
            ->orderBy('cp.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get pipeline statistics.
     *
     * @return array<string, mixed>
     */
    public function getPipelineStats(string $repository, int $days = 7): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        $qb = $this->entityManager
            ->getRepository(CicdPipeline::class)
            ->createQueryBuilder('cp');

        // Total runs
        $totalRuns = $qb
            ->select('COUNT(cp.id)')
            ->where('cp.repository = :repository')
            ->andWhere('cp.startedAt >= :since')
            ->setParameter('repository', $repository)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        // Success rate
        $successfulRuns = $qb
            ->select('COUNT(cp.id)')
            ->where('cp.repository = :repository')
            ->andWhere('cp.startedAt >= :since')
            ->andWhere('cp.conclusion = :conclusion')
            ->setParameter('repository', $repository)
            ->setParameter('since', $since)
            ->setParameter('conclusion', 'success')
            ->getQuery()
            ->getSingleScalarResult();

        // Average duration
        $avgDuration = $qb
            ->select('AVG(cp.duration)')
            ->where('cp.repository = :repository')
            ->andWhere('cp.startedAt >= :since')
            ->andWhere('cp.duration IS NOT NULL')
            ->setParameter('repository', $repository)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_runs' => (int) $totalRuns,
            'successful_runs' => (int) $successfulRuns,
            'success_rate' => $totalRuns > 0 ? round(($successfulRuns / $totalRuns) * 100, 2) : 0,
            'average_duration' => $avgDuration ? round($avgDuration, 2) : null,
            'period_days' => $days,
        ];
    }

    /**
     * Parse workflow run data from GitHub API response.
     */
    private function parseWorkflowRun(array $run, string $repository): array
    {
        return [
            'id' => (string) $run['id'],
            'workflow_id' => (string) $run['workflow_id'],
            'workflow_name' => $run['name'] ?? 'Unknown Workflow',
            'repository' => $repository,
            'status' => $run['status'] ?? 'unknown',
            'conclusion' => $run['conclusion'] ?? 'unknown',
            'branch' => $run['head_branch'] ?? 'unknown',
            'commit_sha' => $run['head_sha'] ?? '',
            'commit_message' => $run['display_title'] ?? null,
            'actor' => $run['actor']['login'] ?? null,
            'event' => $run['event'] ?? 'unknown',
            'html_url' => $run['html_url'] ?? null,
            'started_at' => $run['run_started_at'] ?? $run['created_at'],
            'completed_at' => $run['updated_at'] ?? null,
        ];
    }

    /**
     * Parse workflow data from GitHub API response.
     */
    private function parseWorkflow(array $workflow): array
    {
        return [
            'id' => $workflow['id'],
            'name' => $workflow['name'],
            'path' => $workflow['path'],
            'state' => $workflow['state'],
            'created_at' => $workflow['created_at'],
            'updated_at' => $workflow['updated_at'],
            'html_url' => $workflow['html_url'],
            'badge_url' => $workflow['badge_url'],
        ];
    }

    /**
     * Store workflow run data for historical tracking.
     */
    private function storeWorkflowRun(array $runData): void
    {
        try {
            // Check if run already exists
            $existing = $this->entityManager
                ->getRepository(CicdPipeline::class)
                ->findOneBy(['runId' => $runData['id']]);

            if ($existing !== null) {
                // Update existing record
                $existing->setStatus($runData['status'])
                    ->setConclusion($runData['conclusion']);
                
                if ($runData['completed_at']) {
                    $existing->setCompletedAt(new \DateTimeImmutable($runData['completed_at']));
                    $existing->setDuration($existing->calculateDuration());
                }
            } else {
                // Create new record
                $entity = new CicdPipeline();
                $entity->setRunId($runData['id'])
                    ->setWorkflowId($runData['workflow_id'])
                    ->setWorkflowName($runData['workflow_name'])
                    ->setRepository($runData['repository'])
                    ->setStatus($runData['status'])
                    ->setConclusion($runData['conclusion'])
                    ->setBranch($runData['branch'])
                    ->setCommitSha($runData['commit_sha'])
                    ->setCommitMessage($runData['commit_message'])
                    ->setActor($runData['actor'])
                    ->setEvent($runData['event'])
                    ->setHtmlUrl($runData['html_url'])
                    ->setStartedAt(new \DateTimeImmutable($runData['started_at']));

                if ($runData['completed_at']) {
                    $entity->setCompletedAt(new \DateTimeImmutable($runData['completed_at']));
                    $entity->setDuration($entity->calculateDuration());
                }

                $this->entityManager->persist($entity);
            }

            $this->entityManager->flush();

        } catch (\Exception $e) {
            $this->logger->error('Failed to store workflow run data', [
                'run_id' => $runData['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }
} 