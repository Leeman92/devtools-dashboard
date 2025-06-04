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
     * Get Docker images.
     *
     * @return array<string, mixed>
     */
    public function getImages(): array
    {
        try {
            $images = $this->makeDockerApiRequest('/images/json');
            $result = [];

            foreach ($images as $image) {
                $result[] = $this->parseImageData($image);
            }

            $this->logger->info('Retrieved Docker images', [
                'image_count' => count($result),
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve Docker images', [
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
     * Start a Docker container.
     */
    public function startContainer(string $containerId): array
    {
        try {
            $result = $this->makeDockerApiRequestPost("/containers/{$containerId}/start");
            
            $this->logger->info('Container started successfully', [
                'container_id' => $containerId,
            ]);

            return [
                'success' => true,
                'message' => 'Container started successfully',
                'container_id' => $containerId,
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to start container', [
                'container_id' => $containerId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to start container: ' . $e->getMessage(),
                'container_id' => $containerId,
            ];
        }
    }

    /**
     * Stop a Docker container.
     */
    public function stopContainer(string $containerId): array
    {
        try {
            $result = $this->makeDockerApiRequestPost("/containers/{$containerId}/stop");
            
            $this->logger->info('Container stopped successfully', [
                'container_id' => $containerId,
            ]);

            return [
                'success' => true,
                'message' => 'Container stopped successfully',
                'container_id' => $containerId,
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to stop container', [
                'container_id' => $containerId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to stop container: ' . $e->getMessage(),
                'container_id' => $containerId,
            ];
        }
    }

    /**
     * Restart a Docker container.
     */
    public function restartContainer(string $containerId): array
    {
        try {
            $result = $this->makeDockerApiRequestPost("/containers/{$containerId}/restart");
            
            $this->logger->info('Container restarted successfully', [
                'container_id' => $containerId,
            ]);

            return [
                'success' => true,
                'message' => 'Container restarted successfully',
                'container_id' => $containerId,
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to restart container', [
                'container_id' => $containerId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to restart container: ' . $e->getMessage(),
                'container_id' => $containerId,
            ];
        }
    }

    /**
     * Get real-time container statistics (CPU, memory, etc.).
     */
    public function getContainerStats(string $containerId): array
    {
        try {
            $endpoint = "/containers/{$containerId}/stats?stream=false";
            $stats = $this->makeDockerApiRequest($endpoint);
            
            $this->logger->debug('Retrieved container stats', [
                'container_id' => $containerId,
                'cpu_usage' => $stats['cpu_stats']['cpu_usage']['total_usage'] ?? 'N/A',
                'memory_usage' => $stats['memory_stats']['usage'] ?? 'N/A',
            ]);

            return $stats;

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve container stats', [
                'container_id' => $containerId,
                'error' => $e->getMessage(),
            ]);

            // Return empty stats structure to prevent fatal errors
            return [
                'cpu_stats' => [
                    'cpu_usage' => ['total_usage' => 0],
                    'system_cpu_usage' => 0,
                    'online_cpus' => 1,
                ],
                'precpu_stats' => [
                    'cpu_usage' => ['total_usage' => 0],
                    'system_cpu_usage' => 0,
                ],
                'memory_stats' => [
                    'usage' => 0,
                    'limit' => 1,
                ],
            ];
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
            'created' => $container['Created'] * 1000 ?? null, // Convert to milliseconds for JS
            'network_mode' => $container['HostConfig']['NetworkMode'] ?? '',
        ];
    }

    /**
     * Parse image data from Docker API response.
     */
    private function parseImageData(array $image): array
    {
        return [
            'id' => $image['Id'] ?? '',
            'parent_id' => $image['ParentId'] ?? '',
            'repo_tags' => $image['RepoTags'] ?? [],
            'repo_digests' => $image['RepoDigests'] ?? [],
            'created' => $image['Created'] ?? 0,
            'size' => $image['Size'] ?? 0,
            'virtual_size' => $image['VirtualSize'] ?? 0,
            'shared_size' => $image['SharedSize'] ?? 0,
            'labels' => $image['Labels'] ?? [],
            'containers' => $image['Containers'] ?? -1,
            'short_id' => substr($image['Id'] ?? '', 7, 12),
            'created_at' => $image['Created'] ? date('Y-m-d H:i:s', $image['Created']) : '',
            'size_human' => $this->formatBytes($image['Size'] ?? 0),
            'tags' => $this->formatImageTags($image['RepoTags'] ?? []),
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf('%.1f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Format image tags for display.
     */
    private function formatImageTags(array $tags): array
    {
        $result = [];
        
        foreach ($tags as $tag) {
            if (str_contains($tag, ':')) {
                [$repository, $version] = explode(':', $tag, 2);
                $result[] = [
                    'full' => $tag,
                    'repository' => $repository,
                    'version' => $version,
                ];
            } else {
                $result[] = [
                    'full' => $tag,
                    'repository' => $tag,
                    'version' => 'latest',
                ];
            }
        }
        
        return $result;
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

    /**
     * Make a Docker API request using cURL with Unix socket (POST method).
     */
    private function makeDockerApiRequestPost(string $endpoint): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_UNIX_SOCKET_PATH => $this->dockerSocketPath,
            CURLOPT_URL => 'http://localhost' . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: 0',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($error)) {
            throw new \RuntimeException("cURL error: {$error}");
        }

        // Docker API returns 204 for successful start/stop/restart operations
        if (!in_array($httpCode, [200, 204], true)) {
            // Log the actual response body for debugging
            $this->logger->error('Docker API error response', [
                'endpoint' => $endpoint,
                'http_code' => $httpCode,
                'response_body' => $response,
            ]);
            
            throw new \RuntimeException("HTTP error: {$httpCode} - Response: " . substr($response, 0, 200));
        }

        // Return empty array for 204 responses (successful operations with no content)
        if ($httpCode === 204) {
            return [];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response: ' . json_last_error_msg());
        }

        return $data;
    }
} 