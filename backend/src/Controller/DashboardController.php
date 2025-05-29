<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Dashboard controller for handling main application routes.
 */
final class DashboardController extends AbstractController
{
    public function __construct(
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
} 