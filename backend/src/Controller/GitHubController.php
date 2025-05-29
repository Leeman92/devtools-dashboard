<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GitHubService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GitHub controller for handling GitHub-related API endpoints.
 */
#[Route('/api/github')]
final class GitHubController extends AbstractController
{
    public function __construct(
        private readonly GitHubService $githubService,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/{owner}/{repo}/workflows', name: 'api_github_workflows', methods: ['GET'])]
    public function getWorkflows(string $owner, string $repo): JsonResponse
    {
        $workflows = $this->githubService->getWorkflows($owner, $repo);
        
        return $this->json([
            'repository' => "{$owner}/{$repo}",
            'workflows' => $workflows,
            'count' => count($workflows),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/{owner}/{repo}/runs', name: 'api_github_workflow_runs', methods: ['GET'])]
    public function getWorkflowRuns(string $owner, string $repo, Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 10);
        $runs = $this->githubService->getWorkflowRuns($owner, $repo, $limit);
        
        return $this->json([
            'repository' => "{$owner}/{$repo}",
            'runs' => $runs,
            'count' => count($runs),
            'limit' => $limit,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/{owner}/{repo}', name: 'api_github_repository', methods: ['GET'])]
    public function getRepository(string $owner, string $repo): JsonResponse
    {
        $repository = $this->githubService->getRepository($owner, $repo);
        
        return $this->json([
            'repository' => $repository,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/{owner}/{repo}/stats', name: 'api_github_pipeline_stats', methods: ['GET'])]
    public function getPipelineStats(string $owner, string $repo, Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $stats = $this->githubService->getPipelineStats("{$owner}/{$repo}", $days);
        
        return $this->json([
            'repository' => "{$owner}/{$repo}",
            'stats' => $stats,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/{owner}/{repo}/history', name: 'api_github_pipeline_history', methods: ['GET'])]
    public function getPipelineHistory(string $owner, string $repo, Request $request): JsonResponse
    {
        $hours = (int) $request->query->get('hours', 24);
        $history = $this->githubService->getPipelineHistory("{$owner}/{$repo}", $hours);
        
        return $this->json([
            'repository' => "{$owner}/{$repo}",
            'history' => $history,
            'count' => count($history),
            'period_hours' => $hours,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }
} 