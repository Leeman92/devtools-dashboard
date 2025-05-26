<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Infrastructure Metric entity for tracking system metrics from Prometheus/Grafana.
 */
#[ORM\Entity]
#[ORM\Table(name: 'infrastructure_metrics')]
#[ORM\Index(columns: ['metric_name'], name: 'idx_metric_name')]
#[ORM\Index(columns: ['source'], name: 'idx_source')]
#[ORM\Index(columns: ['recorded_at'], name: 'idx_recorded_at')]
#[ORM\Index(columns: ['metric_name', 'source', 'recorded_at'], name: 'idx_metric_source_time')]
class InfrastructureMetric
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $metricName;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $source;

    #[ORM\Column(type: Types::FLOAT)]
    private float $value;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $labels = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $threshold = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $alertLevel = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $recordedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->recordedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'normal';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMetricName(): string
    {
        return $this->metricName;
    }

    public function setMetricName(string $metricName): self
    {
        $this->metricName = $metricName;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
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

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getThreshold(): ?float
    {
        return $this->threshold;
    }

    public function setThreshold(?float $threshold): self
    {
        $this->threshold = $threshold;
        return $this;
    }

    public function getAlertLevel(): ?string
    {
        return $this->alertLevel;
    }

    public function setAlertLevel(?string $alertLevel): self
    {
        $this->alertLevel = $alertLevel;
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

    /**
     * Check if the metric value exceeds the threshold.
     */
    public function isAboveThreshold(): bool
    {
        return $this->threshold !== null && $this->value > $this->threshold;
    }

    /**
     * Check if the metric is in a critical state.
     */
    public function isCritical(): bool
    {
        return $this->alertLevel === 'critical';
    }

    /**
     * Check if the metric is in a warning state.
     */
    public function isWarning(): bool
    {
        return $this->alertLevel === 'warning';
    }

    /**
     * Get formatted value with unit.
     */
    public function getFormattedValue(): string
    {
        $formatted = number_format($this->value, 2);
        
        if ($this->unit !== null) {
            return $formatted . ' ' . $this->unit;
        }

        return $formatted;
    }

    /**
     * Get display name for the metric.
     */
    public function getDisplayName(): string
    {
        return match ($this->metricName) {
            'cpu_usage' => 'CPU Usage',
            'memory_usage' => 'Memory Usage',
            'disk_usage' => 'Disk Usage',
            'network_in' => 'Network In',
            'network_out' => 'Network Out',
            'load_average' => 'Load Average',
            'response_time' => 'Response Time',
            'error_rate' => 'Error Rate',
            default => ucwords(str_replace('_', ' ', $this->metricName)),
        };
    }

    /**
     * Determine alert level based on value and thresholds.
     */
    public function calculateAlertLevel(): string
    {
        if ($this->threshold === null) {
            return 'normal';
        }

        $percentage = ($this->value / $this->threshold) * 100;

        if ($percentage >= 95) {
            return 'critical';
        }

        if ($percentage >= 80) {
            return 'warning';
        }

        return 'normal';
    }
} 