<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\InfrastructureMetric;
use App\Service\DockerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to collect real-time Docker container metrics.
 */
#[AsCommand(
    name: 'app:collect-metrics',
    description: 'Collect real-time Docker container metrics'
)]
class CollectMetricsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DockerService $dockerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be collected without storing')
            ->addOption('source', null, InputOption::VALUE_OPTIONAL, 'Metrics source', 'docker')
            ->addOption('cleanup-days', null, InputOption::VALUE_OPTIONAL, 'Clean up metrics older than X days', 7);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $dryRun = $input->getOption('dry-run');
        $source = $input->getOption('source');
        $cleanupDays = (int) $input->getOption('cleanup-days');
        
        $io->title('DevTools Dashboard - Metrics Collection');
        
        if ($dryRun) {
            $io->note('DRY RUN MODE - No data will be stored');
        }
        
        try {
            // Collect Docker container metrics
            $containers = $this->dockerService->getContainers();
            $metrics = [];
            $now = new \DateTimeImmutable();
            
            if (empty($containers)) {
                $io->warning('No containers found');
                return Command::SUCCESS;
            }
            
            $io->writeln("Found " . count($containers) . " containers");
            
            // Calculate overall system metrics
            $totalCpuUsage = 0;
            $totalMemoryUsage = 0;
            $totalMemoryLimit = 0;
            $runningContainers = 0;
            
            foreach ($containers as $container) {
                if ($container['state'] === 'running') {
                    $runningContainers++;
                    
                    // Generate realistic metrics based on container state
                    $cpuPercent = $this->calculateCpuUsage($container);
                    $memoryPercent = $this->calculateMemoryUsage($container);
                    
                    $totalCpuUsage += $cpuPercent;
                    $totalMemoryUsage += $memoryPercent;
                }
            }
            
            // Calculate average system metrics
            if ($runningContainers > 0) {
                $avgCpuPercent = $totalCpuUsage / $runningContainers;
                $avgMemoryPercent = $totalMemoryUsage / $runningContainers;
            } else {
                $avgCpuPercent = 0;
                $avgMemoryPercent = 0;
            }
            
            // Create system-level metrics
            $systemMetrics = [
                [
                    'name' => 'cpu_percent',
                    'value' => round($avgCpuPercent, 2),
                    'unit' => '%',
                    'alert_level' => $this->getAlertLevel($avgCpuPercent, 80, 90)
                ],
                [
                    'name' => 'memory_percent', 
                    'value' => round($avgMemoryPercent, 2),
                    'unit' => '%',
                    'alert_level' => $this->getAlertLevel($avgMemoryPercent, 85, 95)
                ],
                [
                    'name' => 'containers_running',
                    'value' => $runningContainers,
                    'unit' => 'count',
                    'alert_level' => 'normal'
                ],
                [
                    'name' => 'containers_total',
                    'value' => count($containers),
                    'unit' => 'count',
                    'alert_level' => 'normal'
                ]
            ];
            
            // Create metric entities
            foreach ($systemMetrics as $metricData) {
                $metric = new InfrastructureMetric();
                $metric->setSource($source);
                $metric->setMetricName($metricData['name']);
                $metric->setValue($metricData['value']);
                $metric->setUnit($metricData['unit']);
                $metric->setAlertLevel($metricData['alert_level']);
                $metric->setRecordedAt($now);
                
                $metrics[] = $metric;
                
                if ($dryRun) {
                    $io->writeln("Would collect: {$metricData['name']} = {$metricData['value']}{$metricData['unit']} ({$metricData['alert_level']})");
                }
            }
            
            if (!$dryRun) {
                // Store metrics
                foreach ($metrics as $metric) {
                    $this->entityManager->persist($metric);
                }
                
                $this->entityManager->flush();
                
                // Cleanup old metrics
                if ($cleanupDays > 0) {
                    $cutoffDate = $now->modify("-{$cleanupDays} days");
                    $deleted = $this->entityManager->createQuery(
                        'DELETE FROM App\Entity\InfrastructureMetric im WHERE im.recordedAt < :cutoff AND im.source = :source'
                    )
                    ->setParameter('cutoff', $cutoffDate)
                    ->setParameter('source', $source)
                    ->execute();
                    
                    if ($deleted > 0) {
                        $io->writeln("Cleaned up {$deleted} old metrics (older than {$cleanupDays} days)");
                    }
                }
                
                $io->success("Collected " . count($metrics) . " metrics from {$runningContainers} running containers");
            } else {
                $io->note("Would store " . count($metrics) . " metrics");
            }
            
        } catch (\Exception $e) {
            $io->error("Failed to collect metrics: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    private function calculateCpuUsage(array $container): float
    {
        try {
            // Get real CPU stats from Docker API
            $stats = $this->dockerService->getContainerStats($container['id']);
            
            if (!isset($stats['cpu_stats']) || !isset($stats['precpu_stats'])) {
                // Fallback to minimal usage if stats unavailable
                return 0.5;
            }
            
            $cpuStats = $stats['cpu_stats'];
            $preCpuStats = $stats['precpu_stats'];
            
            // Calculate CPU percentage using Docker formula
            $cpuDelta = $cpuStats['cpu_usage']['total_usage'] - $preCpuStats['cpu_usage']['total_usage'];
            $systemDelta = $cpuStats['system_cpu_usage'] - $preCpuStats['system_cpu_usage'];
            $numberCpus = $cpuStats['online_cpus'] ?? 1;
            
            if ($systemDelta > 0 && $cpuDelta > 0) {
                $cpuPercent = ($cpuDelta / $systemDelta) * $numberCpus * 100.0;
                return round(min(100.0, max(0.0, $cpuPercent)), 2);
            }
            
            return 0.0;
        } catch (\Exception $e) {
            // Log error and return minimal usage
            error_log("Failed to get CPU stats for container {$container['id']}: " . $e->getMessage());
            return 0.1; // Very low CPU when stats unavailable
        }
    }
    
    private function calculateMemoryUsage(array $container): float
    {
        try {
            // Get real memory stats from Docker API
            $stats = $this->dockerService->getContainerStats($container['id']);
            
            if (!isset($stats['memory_stats']['usage']) || !isset($stats['memory_stats']['limit'])) {
                // Fallback to minimal usage if stats unavailable
                return 5.0;
            }
            
            $memoryUsage = $stats['memory_stats']['usage'];
            $memoryLimit = $stats['memory_stats']['limit'];
            
            // Calculate memory percentage
            if ($memoryLimit > 0) {
                $memoryPercent = ($memoryUsage / $memoryLimit) * 100.0;
                return round(min(100.0, max(0.0, $memoryPercent)), 2);
            }
            
            return 0.0;
        } catch (\Exception $e) {
            // Log error and return minimal usage
            error_log("Failed to get memory stats for container {$container['id']}: " . $e->getMessage());
            return 5.0; // Low memory when stats unavailable
        }
    }
    
    private function getAlertLevel(float $value, float $warningThreshold, float $criticalThreshold): string
    {
        if ($value >= $criticalThreshold) {
            return 'critical';
        }
        if ($value >= $warningThreshold) {
            return 'warning';
        }
        return 'normal';
    }
} 