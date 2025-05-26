<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * CI/CD Pipeline entity for tracking GitHub Actions workflows and runs.
 */
#[ORM\Entity]
#[ORM\Table(name: 'cicd_pipelines')]
#[ORM\Index(columns: ['repository'], name: 'idx_repository')]
#[ORM\Index(columns: ['status'], name: 'idx_status')]
#[ORM\Index(columns: ['started_at'], name: 'idx_started_at')]
#[ORM\Index(columns: ['workflow_name'], name: 'idx_workflow_name')]
class CicdPipeline
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $runId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $workflowId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $workflowName;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $repository;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $conclusion;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $branch;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $commitSha;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $commitMessage = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $actor = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $event;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $htmlUrl = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

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

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function setRunId(string $runId): self
    {
        $this->runId = $runId;
        return $this;
    }

    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }

    public function setWorkflowId(string $workflowId): self
    {
        $this->workflowId = $workflowId;
        return $this;
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function setWorkflowName(string $workflowName): self
    {
        $this->workflowName = $workflowName;
        return $this;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }

    public function setRepository(string $repository): self
    {
        $this->repository = $repository;
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

    public function getConclusion(): string
    {
        return $this->conclusion;
    }

    public function setConclusion(string $conclusion): self
    {
        $this->conclusion = $conclusion;
        return $this;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    public function setBranch(string $branch): self
    {
        $this->branch = $branch;
        return $this;
    }

    public function getCommitSha(): string
    {
        return $this->commitSha;
    }

    public function setCommitSha(string $commitSha): self
    {
        $this->commitSha = $commitSha;
        return $this;
    }

    public function getCommitMessage(): ?string
    {
        return $this->commitMessage;
    }

    public function setCommitMessage(?string $commitMessage): self
    {
        $this->commitMessage = $commitMessage;
        return $this;
    }

    public function getActor(): ?string
    {
        return $this->actor;
    }

    public function setActor(?string $actor): self
    {
        $this->actor = $actor;
        return $this;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getHtmlUrl(): ?string
    {
        return $this->htmlUrl;
    }

    public function setHtmlUrl(?string $htmlUrl): self
    {
        $this->htmlUrl = $htmlUrl;
        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
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
     * Check if the pipeline is currently running.
     */
    public function isRunning(): bool
    {
        return in_array($this->status, ['queued', 'in_progress'], true);
    }

    /**
     * Check if the pipeline completed successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->conclusion === 'success';
    }

    /**
     * Check if the pipeline failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'completed' && in_array($this->conclusion, ['failure', 'cancelled', 'timed_out'], true);
    }

    /**
     * Get a human-readable status.
     */
    public function getDisplayStatus(): string
    {
        if ($this->isRunning()) {
            return 'running';
        }

        if ($this->isSuccessful()) {
            return 'success';
        }

        if ($this->isFailed()) {
            return 'failed';
        }

        return $this->status;
    }

    /**
     * Calculate duration if completed.
     */
    public function calculateDuration(): ?int
    {
        if ($this->completedAt === null) {
            return null;
        }

        return $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }
} 