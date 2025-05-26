<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DockerService as DockerServiceEntity;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Docker Service for interacting with Docker API to monitor containers and swarm services.
 */
final readonly class DockerService
{
    public function __construct(
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
            $services = $this->makeDockerApiRequest('/services');
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

        } catch (\Exception $e) {
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
            $containers = $this->makeDockerApiRequest('/containers/json?all=true');
            $result = [];

            foreach ($containers as $container) {
                $result[] = $this->parseContainerData($container);
            }

            $this->logger->info('Retrieved Docker containers', [
                'container_count' => count($result),
            ]);

            return $result;

        } catch (\Exception $e) {
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
            $endpoint = "/containers/{$containerId}/logs?stdout=true&stderr=true&tail={$lines}&timestamps=true";
            $logs = $this->makeDockerApiRequestRaw($endpoint);
            return $this->parseLogs($logs);

        } catch (\Exception $e) {
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
            $endpoint = "/services/{$serviceId}/logs?stdout=true&stderr=true&tail={$lines}&timestamps=true";
            $logs = $this->makeDockerApiRequestRaw($endpoint);
            return $this->parseLogs($logs);

        } catch (\Exception $e) {
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

    /**
     * Make a Docker API request using cURL with Unix socket.
     */
    private function makeDockerApiRequest(string $endpoint): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_UNIX_SOCKET_PATH => $this->dockerSocketPath,
            CURLOPT_URL => 'http://localhost' . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($error)) {
            throw new \RuntimeException("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("HTTP error: {$httpCode}");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Make a Docker API request using cURL with Unix socket (raw response).
     */
    private function makeDockerApiRequestRaw(string $endpoint): string
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_UNIX_SOCKET_PATH => $this->dockerSocketPath,
            CURLOPT_URL => 'http://localhost' . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($error)) {
            throw new \RuntimeException("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("HTTP error: {$httpCode}");
        }

        return $response;
    }
} 