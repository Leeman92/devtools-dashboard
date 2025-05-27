<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DockerService;
use App\Service\GitHubService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Dashboard controller for handling main application routes.
 */
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DockerService $dockerService,
        private readonly GitHubService $githubService,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/', name: 'dashboard_home', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to Patrick Lehmann\'s DevTools Dashboard',
            'status' => 'operational',
            'version' => '1.0.0',
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        $this->logger->info('Health check requested', [
            'environment' => $this->getParameter('kernel.environment'),
            'timestamp' => new \DateTimeImmutable(),
        ]);

        return $this->json([
            'status' => 'healthy',
            'timestamp' => new \DateTimeImmutable(),
            'environment' => $this->getParameter('kernel.environment'),
        ]);
    }

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        $this->logger->info('Dashboard accessed', [
            'timestamp' => new \DateTimeImmutable(),
        ]);

        return $this->json([
            'dashboard' => [
                'title' => 'DevTools Dashboard',
                'description' => 'A comprehensive dashboard for development tools and services',
                'features' => [
                    'Service monitoring',
                    'Infrastructure overview',
                    'Development tools',
                    'CI/CD pipeline status',
                ],
            ],
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/test-logging', name: 'api_test_logging', methods: ['GET'])]
    public function testLogging(): JsonResponse
    {
        $this->logger->debug('Debug level log message');
        $this->logger->info('Info level log message', ['test' => true]);
        $this->logger->notice('Notice level log message');
        $this->logger->warning('Warning level log message', ['warning' => 'test']);
        $this->logger->error('Error level log message', ['error' => 'test']);

        return $this->json([
            'message' => 'Logging test completed',
            'levels_tested' => ['debug', 'info', 'notice', 'warning', 'error'],
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/docker/services', name: 'api_docker_services', methods: ['GET'])]
    public function dockerServices(): JsonResponse
    {
        $services = $this->dockerService->getSwarmServices();
        
        return $this->json([
            'services' => $services,
            'count' => count($services),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/docker/containers', name: 'api_docker_containers', methods: ['GET'])]
    public function dockerContainers(): JsonResponse
    {
        $containers = $this->dockerService->getContainers();
        
        return $this->json([
            'containers' => $containers,
            'count' => count($containers),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/docker/services/{serviceId}/logs', name: 'api_docker_service_logs', methods: ['GET'])]
    public function dockerServiceLogs(string $serviceId, Request $request): JsonResponse
    {
        $lines = (int) $request->query->get('lines', 100);
        $logs = $this->dockerService->getServiceLogs($serviceId, $lines);
        
        return $this->json([
            'service_id' => $serviceId,
            'logs' => $logs,
            'count' => count($logs),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/docker/containers/{containerId}/logs', name: 'api_docker_container_logs', methods: ['GET'])]
    public function dockerContainerLogs(string $containerId, Request $request): JsonResponse
    {
        $lines = (int) $request->query->get('lines', 100);
        $logs = $this->dockerService->getContainerLogs($containerId, $lines);
        
        return $this->json([
            'container_id' => $containerId,
            'logs' => $logs,
            'count' => count($logs),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/docker/services/{serviceName}/history', name: 'api_docker_service_history', methods: ['GET'])]
    public function dockerServiceHistory(string $serviceName, Request $request): JsonResponse
    {
        $hours = (int) $request->query->get('hours', 24);
        $history = $this->dockerService->getServiceHistory($serviceName, $hours);
        
        return $this->json([
            'service_name' => $serviceName,
            'history' => $history,
            'count' => count($history),
            'period_hours' => $hours,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/github/{owner}/{repo}/workflows', name: 'api_github_workflows', methods: ['GET'])]
    public function githubWorkflows(string $owner, string $repo): JsonResponse
    {
        $workflows = $this->githubService->getWorkflows($owner, $repo);
        
        return $this->json([
            'repository' => "{$owner}/{$repo}",
            'workflows' => $workflows,
            'count' => count($workflows),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/github/{owner}/{repo}/runs', name: 'api_github_workflow_runs', methods: ['GET'])]
    public function githubWorkflowRuns(string $owner, string $repo, Request $request): JsonResponse
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

    #[Route('/api/github/{owner}/{repo}', name: 'api_github_repository', methods: ['GET'])]
    public function githubRepository(string $owner, string $repo): JsonResponse
    {
        $repository = $this->githubService->getRepository($owner, $repo);
        
        return $this->json([
            'repository' => $repository,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/github/{owner}/{repo}/stats', name: 'api_github_pipeline_stats', methods: ['GET'])]
    public function githubPipelineStats(string $owner, string $repo, Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 7);
        $stats = $this->githubService->getPipelineStats("{$owner}/{$repo}", $days);
        
        return $this->json([
            'repository' => "{$owner}/{$repo}",
            'stats' => $stats,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/github/{owner}/{repo}/history', name: 'api_github_pipeline_history', methods: ['GET'])]
    public function githubPipelineHistory(string $owner, string $repo, Request $request): JsonResponse
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