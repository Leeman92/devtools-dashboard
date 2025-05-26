<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DockerService as DockerServiceEntity;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Docker Service for interacting with Docker API to monitor containers and swarm services.
 */
final readonly class DockerService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private string $dockerSocketPath = '/var/run/docker.sock',
    ) {}

    /**
     * Get all Docker Swarm services.
     *
     * @return array<string, mixed>
     */
    public function getSwarmServices(): array
    {
        try {
            $response = $this->httpClient->request('GET', 'http://localhost/services', [
                'base_uri' => 'unix://' . $this->dockerSocketPath,
                'timeout' => 10,
            ]);

            $services = $response->toArray();
            $result = [];

            foreach ($services as $service) {
                $serviceData = $this->parseServiceData($service);
                $result[] = $serviceData;
                
                // Store historical data
                $this->storeServiceData($serviceData);
            }

            $this->logger->info('Retrieved Docker Swarm services', [
                'service_count' => count($result),
            ]);

            return $result;

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to retrieve Docker Swarm services', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get Docker containers.
     *
     * @return array<string, mixed>
     */
    public function getContainers(): array
    {
        try {
            $response = $this->httpClient->request('GET', 'http://localhost/containers/json', [
                'base_uri' => 'unix://' . $this->dockerSocketPath,
                'query' => ['all' => 'true'],
                'timeout' => 10,
            ]);

            $containers = $response->toArray();
            $result = [];

            foreach ($containers as $container) {
                $result[] = $this->parseContainerData($container);
            }

            $this->logger->info('Retrieved Docker containers', [
                'container_count' => count($result),
            ]);

            return $result;

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to retrieve Docker containers', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get container logs.
     */
    public function getContainerLogs(string $containerId, int $lines = 100): array
    {
        try {
            $response = $this->httpClient->request('GET', "http://localhost/containers/{$containerId}/logs", [
                'base_uri' => 'unix://' . $this->dockerSocketPath,
                'query' => [
                    'stdout' => 'true',
                    'stderr' => 'true',
                    'tail' => $lines,
                    'timestamps' => 'true',
                ],
                'timeout' => 10,
            ]);

            $logs = $response->getContent();
            return $this->parseLogs($logs);

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to retrieve container logs', [
                'container_id' => $containerId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get service logs.
     */
    public function getServiceLogs(string $serviceId, int $lines = 100): array
    {
        try {
            $response = $this->httpClient->request('GET', "http://localhost/services/{$serviceId}/logs", [
                'base_uri' => 'unix://' . $this->dockerSocketPath,
                'query' => [
                    'stdout' => 'true',
                    'stderr' => 'true',
                    'tail' => $lines,
                    'timestamps' => 'true',
                ],
                'timeout' => 10,
            ]);

            $logs = $response->getContent();
            return $this->parseLogs($logs);

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to retrieve service logs', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get historical service data.
     *
     * @return DockerServiceEntity[]
     */
    public function getServiceHistory(string $serviceName, int $hours = 24): array
    {
        $since = new \DateTimeImmutable("-{$hours} hours");

        return $this->entityManager
            ->getRepository(DockerServiceEntity::class)
            ->createQueryBuilder('ds')
            ->where('ds.serviceName = :serviceName')
            ->andWhere('ds.recordedAt >= :since')
            ->setParameter('serviceName', $serviceName)
            ->setParameter('since', $since)
            ->orderBy('ds.recordedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Parse service data from Docker API response.
     */
    private function parseServiceData(array $service): array
    {
        $spec = $service['Spec'] ?? [];
        $status = $service['ServiceStatus'] ?? [];
        
        return [
            'id' => $service['ID'] ?? '',
            'name' => $spec['Name'] ?? 'unknown',
            'image' => $spec['TaskTemplate']['ContainerSpec']['Image'] ?? '',
            'replicas' => $spec['Mode']['Replicated']['Replicas'] ?? 1,
            'running_replicas' => $status['RunningTasks'] ?? 0,
            'desired_replicas' => $status['DesiredTasks'] ?? 0,
            'ports' => $this->parsePorts($spec['EndpointSpec']['Ports'] ?? []),
            'labels' => $spec['Labels'] ?? [],
            'created_at' => $service['CreatedAt'] ?? null,
            'updated_at' => $service['UpdatedAt'] ?? null,
            'version' => $service['Version']['Index'] ?? 0,
        ];
    }

    /**
     * Parse container data from Docker API response.
     */
    private function parseContainerData(array $container): array
    {
        return [
            'id' => $container['Id'] ?? '',
            'name' => ltrim($container['Names'][0] ?? '', '/'),
            'image' => $container['Image'] ?? '',
            'status' => $container['Status'] ?? '',
            'state' => $container['State'] ?? '',
            'ports' => $this->parsePorts($container['Ports'] ?? []),
            'labels' => $container['Labels'] ?? [],
            'created' => $container['Created'] ?? null,
            'network_mode' => $container['HostConfig']['NetworkMode'] ?? '',
        ];
    }

    /**
     * Parse ports configuration.
     */
    private function parsePorts(array $ports): array
    {
        $result = [];
        
        foreach ($ports as $port) {
            if (isset($port['PublishedPort'], $port['TargetPort'])) {
                $result[] = [
                    'published' => $port['PublishedPort'],
                    'target' => $port['TargetPort'],
                    'protocol' => $port['Protocol'] ?? 'tcp',
                ];
            }
        }

        return $result;
    }

    /**
     * Parse Docker logs.
     */
    private function parseLogs(string $logs): array
    {
        $lines = explode("\n", trim($logs));
        $result = [];

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            // Remove Docker log headers (8 bytes)
            if (strlen($line) > 8) {
                $line = substr($line, 8);
            }

            // Parse timestamp if present
            if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z)\s+(.*)$/', $line, $matches)) {
                $result[] = [
                    'timestamp' => $matches[1],
                    'message' => $matches[2],
                ];
            } else {
                $result[] = [
                    'timestamp' => null,
                    'message' => $line,
                ];
            }
        }

        return array_reverse($result); // Most recent first
    }

    /**
     * Store service data for historical tracking.
     */
    private function storeServiceData(array $serviceData): void
    {
        try {
            $entity = new DockerServiceEntity();
            $entity->setServiceId($serviceData['id'])
                ->setServiceName($serviceData['name'])
                ->setStatus($this->determineServiceStatus($serviceData))
                ->setReplicas($serviceData['replicas'])
                ->setRunningReplicas($serviceData['running_replicas'])
                ->setImage($serviceData['image'])
                ->setPorts($serviceData['ports'])
                ->setLabels($serviceData['labels']);

            $this->entityManager->persist($entity);
            $this->entityManager->flush();

        } catch (\Exception $e) {
            $this->logger->error('Failed to store service data', [
                'service_name' => $serviceData['name'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine service status based on replica counts.
     */
    private function determineServiceStatus(array $serviceData): string
    {
        $running = $serviceData['running_replicas'];
        $desired = $serviceData['replicas'];

        if ($running === 0) {
            return 'down';
        }

        if ($running < $desired) {
            return 'degraded';
        }

        if ($running === $desired) {
            return 'running';
        }

        return 'unknown';
    }
} 