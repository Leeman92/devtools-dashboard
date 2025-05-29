<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InfrastructureMetric;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Infrastructure controller for handling infrastructure metrics and monitoring.
 */
#[Route('/api/infrastructure')]
final class InfrastructureController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/metrics', name: 'api_infrastructure_metrics', methods: ['GET'])]
    public function metrics(Request $request): JsonResponse
    {
        $source = $request->query->get('source');
        $metricName = $request->query->get('metric');
        $hours = (int) $request->query->get('hours', 1);
        
        $since = new \DateTimeImmutable("-{$hours} hours");
        
        $qb = $this->entityManager
            ->getRepository(InfrastructureMetric::class)
            ->createQueryBuilder('im')
            ->where('im.recordedAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('im.recordedAt', 'DESC');

        if ($source) {
            $qb->andWhere('im.source = :source')
               ->setParameter('source', $source);
        }

        if ($metricName) {
            $qb->andWhere('im.metricName = :metricName')
               ->setParameter('metricName', $metricName);
        }

        $metrics = $qb->getQuery()->getResult();

        return $this->json([
            'metrics' => $metrics,
            'count' => count($metrics),
            'filters' => [
                'source' => $source,
                'metric_name' => $metricName,
                'hours' => $hours,
            ],
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/metrics/latest', name: 'api_infrastructure_metrics_latest', methods: ['GET'])]
    public function latestMetrics(): JsonResponse
    {
        // Get the latest metric for each source/metric combination
        $sql = '
            SELECT im1.*
            FROM infrastructure_metrics im1
            INNER JOIN (
                SELECT source, metric_name, MAX(recorded_at) as max_recorded_at
                FROM infrastructure_metrics
                GROUP BY source, metric_name
            ) im2 ON im1.source = im2.source 
                AND im1.metric_name = im2.metric_name 
                AND im1.recorded_at = im2.max_recorded_at
            ORDER BY im1.source, im1.metric_name
        ';

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        $metrics = $result->fetchAllAssociative();

        return $this->json([
            'metrics' => $metrics,
            'count' => count($metrics),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/metrics/summary', name: 'api_infrastructure_metrics_summary', methods: ['GET'])]
    public function metricsSummary(Request $request): JsonResponse
    {
        $hours = (int) $request->query->get('hours', 24);
        $since = new \DateTimeImmutable("-{$hours} hours");

        // Get metrics summary by source
        $qb = $this->entityManager
            ->getRepository(InfrastructureMetric::class)
            ->createQueryBuilder('im')
            ->select('im.source, im.metricName, COUNT(im.id) as count, AVG(im.value) as avg_value, MAX(im.value) as max_value, MIN(im.value) as min_value')
            ->where('im.recordedAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('im.source, im.metricName')
            ->orderBy('im.source, im.metricName');

        $summary = $qb->getQuery()->getResult();

        // Get alert counts
        $alertQb = $this->entityManager
            ->getRepository(InfrastructureMetric::class)
            ->createQueryBuilder('im')
            ->select('im.alertLevel, COUNT(im.id) as count')
            ->where('im.recordedAt >= :since')
            ->andWhere('im.alertLevel IS NOT NULL')
            ->andWhere('im.alertLevel != :normal')
            ->setParameter('since', $since)
            ->setParameter('normal', 'normal')
            ->groupBy('im.alertLevel');

        $alerts = $alertQb->getQuery()->getResult();

        return $this->json([
            'summary' => $summary,
            'alerts' => $alerts,
            'period_hours' => $hours,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/metrics/sources', name: 'api_infrastructure_metrics_sources', methods: ['GET'])]
    public function metricsSources(): JsonResponse
    {
        $qb = $this->entityManager
            ->getRepository(InfrastructureMetric::class)
            ->createQueryBuilder('im')
            ->select('DISTINCT im.source')
            ->orderBy('im.source');

        $sources = array_column($qb->getQuery()->getResult(), 'source');

        return $this->json([
            'sources' => $sources,
            'count' => count($sources),
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/metrics/names', name: 'api_infrastructure_metrics_names', methods: ['GET'])]
    public function metricsNames(Request $request): JsonResponse
    {
        $source = $request->query->get('source');
        
        $qb = $this->entityManager
            ->getRepository(InfrastructureMetric::class)
            ->createQueryBuilder('im')
            ->select('DISTINCT im.metricName')
            ->orderBy('im.metricName');

        if ($source) {
            $qb->where('im.source = :source')
               ->setParameter('source', $source);
        }

        $metricNames = array_column($qb->getQuery()->getResult(), 'metricName');

        return $this->json([
            'metric_names' => $metricNames,
            'count' => count($metricNames),
            'source' => $source,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/health', name: 'api_infrastructure_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        // Get latest metrics to determine overall health
        $since = new \DateTimeImmutable('-5 minutes');
        
        $criticalMetrics = $this->entityManager
            ->getRepository(InfrastructureMetric::class)
            ->createQueryBuilder('im')
            ->where('im.recordedAt >= :since')
            ->andWhere('im.alertLevel = :critical')
            ->setParameter('since', $since)
            ->setParameter('critical', 'critical')
            ->getQuery()
            ->getResult();

        $warningMetrics = $this->entityManager
            ->getRepository(InfrastructureMetric::class)
            ->createQueryBuilder('im')
            ->where('im.recordedAt >= :since')
            ->andWhere('im.alertLevel = :warning')
            ->setParameter('since', $since)
            ->setParameter('warning', 'warning')
            ->getQuery()
            ->getResult();

        $overallStatus = 'healthy';
        if (count($criticalMetrics) > 0) {
            $overallStatus = 'critical';
        } elseif (count($warningMetrics) > 0) {
            $overallStatus = 'warning';
        }

        return $this->json([
            'status' => $overallStatus,
            'critical_count' => count($criticalMetrics),
            'warning_count' => count($warningMetrics),
            'critical_metrics' => $criticalMetrics,
            'warning_metrics' => $warningMetrics,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/metrics/chart/{source}/{metricName}', name: 'api_infrastructure_metrics_chart', methods: ['GET'])]
    public function metricsChart(string $source, string $metricName, Request $request): JsonResponse
    {
        $hours = (int) $request->query->get('hours', 24);
        
        $since = new \DateTimeImmutable("-{$hours} hours");

        // Determine aggregation interval based on time period
        if ($hours <= 4) {
            // For 4 hours or less, group by 5-minute intervals
            $interval = '5 minutes';
            $formatString = 'Y-m-d H:i:00'; // Group by 5-minute intervals
            $roundToMinutes = 5;
        } elseif ($hours <= 12) {
            // For 12 hours or less, group by 15-minute intervals
            $interval = '15 minutes';
            $formatString = 'Y-m-d H:i:00';
            $roundToMinutes = 15;
        } elseif ($hours <= 48) {
            // For 48 hours or less, group by 1-hour intervals
            $interval = '1 hour';
            $formatString = 'Y-m-d H:00:00';
            $roundToMinutes = 60;
        } else {
            // For longer periods, group by 4-hour intervals
            $interval = '4 hours';
            $formatString = 'Y-m-d H:00:00';
            $roundToMinutes = 240;
        }

        $qb = $this->entityManager
            ->getRepository(InfrastructureMetric::class)
            ->createQueryBuilder('im')
            ->where('im.source = :source')
            ->andWhere('im.metricName = :metricName')
            ->andWhere('im.recordedAt >= :since')
            ->setParameter('source', $source)
            ->setParameter('metricName', $metricName)
            ->setParameter('since', $since)
            ->orderBy('im.recordedAt', 'ASC');

        $metrics = $qb->getQuery()->getResult();

        // Group metrics by time interval for charting
        $chartData = [];
        foreach ($metrics as $metric) {
            $recordedAt = $metric->getRecordedAt();
            
            // Round to the appropriate interval
            if ($roundToMinutes < 60) {
                // Round to nearest X minutes
                $minutes = (int) $recordedAt->format('i');
                $roundedMinutes = floor($minutes / $roundToMinutes) * $roundToMinutes;
                $timeKey = $recordedAt->format('Y-m-d H:') . sprintf('%02d:00', $roundedMinutes);
            } else {
                // Round to hours or larger intervals
                if ($roundToMinutes === 60) {
                    $timeKey = $recordedAt->format('Y-m-d H:00:00');
                } else {
                    // 4-hour intervals
                    $hour = (int) $recordedAt->format('H');
                    $roundedHour = floor($hour / 4) * 4;
                    $timeKey = $recordedAt->format('Y-m-d ') . sprintf('%02d:00:00', $roundedHour);
                }
            }
            
            if (!isset($chartData[$timeKey])) {
                $chartData[$timeKey] = [
                    'timestamp' => $timeKey,
                    'values' => [],
                    'avg_value' => 0,
                    'min_value' => null,
                    'max_value' => null,
                ];
            }
            
            $chartData[$timeKey]['values'][] = $metric->getValue();
        }

        // Calculate aggregates for each time bucket
        foreach ($chartData as &$bucket) {
            $values = $bucket['values'];
            $bucket['avg_value'] = array_sum($values) / count($values);
            $bucket['min_value'] = min($values);
            $bucket['max_value'] = max($values);
            unset($bucket['values']); // Remove raw values to reduce response size
        }

        return $this->json([
            'source' => $source,
            'metric_name' => $metricName,
            'chart_data' => array_values($chartData),
            'period_hours' => $hours,
            'interval' => $interval,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }
} 