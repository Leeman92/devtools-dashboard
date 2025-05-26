<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Docker Service entity for tracking Docker Swarm services and containers.
 */
#[ORM\Entity]
#[ORM\Table(name: 'docker_services')]
#[ORM\Index(columns: ['service_name'], name: 'idx_service_name')]
#[ORM\Index(columns: ['status'], name: 'idx_status')]
#[ORM\Index(columns: ['recorded_at'], name: 'idx_recorded_at')]
class DockerService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $serviceId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $serviceName;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status;

    #[ORM\Column(type: Types::INTEGER)]
    private int $replicas;

    #[ORM\Column(type: Types::INTEGER)]
    private int $runningReplicas;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $image;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $ports = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $environment = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $labels = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $recordedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->recordedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function setServiceId(string $serviceId): self
    {
        $this->serviceId = $serviceId;
        return $this;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function setServiceName(string $serviceName): self
    {
        $this->serviceName = $serviceName;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getReplicas(): int
    {
        return $this->replicas;
    }

    public function setReplicas(int $replicas): self
    {
        $this->replicas = $replicas;
        return $this;
    }

    public function getRunningReplicas(): int
    {
        return $this->runningReplicas;
    }

    public function setRunningReplicas(int $runningReplicas): self
    {
        $this->runningReplicas = $runningReplicas;
        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getPorts(): ?array
    {
        return $this->ports;
    }

    public function setPorts(?array $ports): self
    {
        $this->ports = $ports;
        return $this;
    }

    public function getEnvironment(): ?array
    {
        return $this->environment;
    }

    public function setEnvironment(?array $environment): self
    {
        $this->environment = $environment;
        return $this;
    }

    public function getLabels(): ?array
    {
        return $this->labels;
    }

    public function setLabels(?array $labels): self
    {
        $this->labels = $labels;
        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): self
    {
        $this->lastError = $lastError;
        return $this;
    }

    public function getRecordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function setRecordedAt(\DateTimeImmutable $recordedAt): self
    {
        $this->recordedAt = $recordedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Check if the service is healthy (all replicas running).
     */
    public function isHealthy(): bool
    {
        return $this->runningReplicas === $this->replicas && $this->status === 'running';
    }

    /**
     * Get health status as string.
     */
    public function getHealthStatus(): string
    {
        if ($this->isHealthy()) {
            return 'healthy';
        }

        if ($this->runningReplicas === 0) {
            return 'down';
        }

        if ($this->runningReplicas < $this->replicas) {
            return 'degraded';
        }

        return 'unknown';
    }
} 