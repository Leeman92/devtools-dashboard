<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DockerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Docker controller for handling Docker-related API endpoints.
 */
#[Route('/api/docker')]
final class DockerController extends AbstractController
{
    public function __construct(
        private readonly DockerService $dockerService,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/services', name: 'api_docker_services', methods: ['GET'])]
    public function getServices(): JsonResponse
    {
        $services = $this->dockerService->getSwarmServices();
        
        return $this->json([
            'services' => $services,
            'count' => count($services),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/containers', name: 'api_docker_containers', methods: ['GET'])]
    public function getContainers(): JsonResponse
    {
        $containers = $this->dockerService->getContainers();
        
        return $this->json([
            'containers' => $containers,
            'count' => count($containers),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/services/{serviceId}/logs', name: 'api_docker_service_logs', methods: ['GET'])]
    public function getServiceLogs(string $serviceId, Request $request): JsonResponse
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

    #[Route('/containers/{containerId}/logs', name: 'api_docker_container_logs', methods: ['GET'])]
    public function getContainerLogs(string $containerId, Request $request): JsonResponse
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

    #[Route('/containers/{containerId}/start', name: 'api_docker_container_start', methods: ['POST'])]
    public function startContainer(string $containerId): JsonResponse
    {
        $this->logger->info('Container start requested', [
            'container_id' => $containerId,
        ]);

        $result = $this->dockerService->startContainer($containerId);
        
        return $this->json([
            'action' => 'start',
            'container_id' => $containerId,
            'success' => $result['success'],
            'message' => $result['message'],
            'timestamp' => new \DateTimeImmutable(),
        ], $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }

    #[Route('/containers/{containerId}/stop', name: 'api_docker_container_stop', methods: ['POST'])]
    public function stopContainer(string $containerId): JsonResponse
    {
        $this->logger->info('Container stop requested', [
            'container_id' => $containerId,
        ]);

        $result = $this->dockerService->stopContainer($containerId);
        
        return $this->json([
            'action' => 'stop',
            'container_id' => $containerId,
            'success' => $result['success'],
            'message' => $result['message'],
            'timestamp' => new \DateTimeImmutable(),
        ], $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }

    #[Route('/containers/{containerId}/restart', name: 'api_docker_container_restart', methods: ['POST'])]
    public function restartContainer(string $containerId): JsonResponse
    {
        $this->logger->info('Container restart requested', [
            'container_id' => $containerId,
        ]);

        $result = $this->dockerService->restartContainer($containerId);
        
        return $this->json([
            'action' => 'restart',
            'container_id' => $containerId,
            'success' => $result['success'],
            'message' => $result['message'],
            'timestamp' => new \DateTimeImmutable(),
        ], $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }

    #[Route('/services/{serviceName}/history', name: 'api_docker_service_history', methods: ['GET'])]
    public function getServiceHistory(string $serviceName, Request $request): JsonResponse
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

    #[Route('/images', name: 'api_docker_images', methods: ['GET'])]
    public function getImages(): JsonResponse
    {
        $images = $this->dockerService->getImages();
        
        return $this->json([
            'images' => $images,
            'count' => count($images),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }
} 