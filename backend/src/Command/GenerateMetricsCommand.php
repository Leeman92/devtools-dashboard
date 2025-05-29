<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\InfrastructureMetric;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-metrics',
    description: 'Generate sample metrics data for demonstration'
)]
class GenerateMetricsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('hours', null, InputOption::VALUE_OPTIONAL, 'Number of hours of data to generate', 2)
            ->addOption('interval', null, InputOption::VALUE_OPTIONAL, 'Interval in minutes between data points', 5);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $hours = (int) $input->getOption('hours');
        $interval = (int) $input->getOption('interval');
        
        $io->title('Generating Sample Metrics Data');
        $io->writeln("Generating {$hours} hours of data with {$interval}-minute intervals");
        
        $dataPoints = ($hours * 60) / $interval;
        $now = new \DateTimeImmutable();
        
        $metrics = [];
        
        for ($i = 0; $i < $dataPoints; $i++) {
            $timestamp = $now->modify("-{$i} minutes");
            
            // Generate CPU metrics
            $cpuMetric = new InfrastructureMetric();
            $cpuMetric->setSource('docker');
            $cpuMetric->setMetricName('cpu_percent');
            $cpuMetric->setValue($this->generateCPUValue($i, $dataPoints));
            $cpuMetric->setUnit('%');
            $cpuMetric->setRecordedAt($timestamp);
            $cpuMetric->setAlertLevel($this->getAlertLevel($cpuMetric->getValue(), 80, 90));
            
            $metrics[] = $cpuMetric;
            
            // Generate Memory metrics
            $memoryMetric = new InfrastructureMetric();
            $memoryMetric->setSource('docker');
            $memoryMetric->setMetricName('memory_percent');
            $memoryMetric->setValue($this->generateMemoryValue($i, $dataPoints));
            $memoryMetric->setUnit('%');
            $memoryMetric->setRecordedAt($timestamp);
            $memoryMetric->setAlertLevel($this->getAlertLevel($memoryMetric->getValue(), 85, 95));
            
            $metrics[] = $memoryMetric;
            
            // Generate Network metrics
            $networkRxMetric = new InfrastructureMetric();
            $networkRxMetric->setSource('docker');
            $networkRxMetric->setMetricName('network_rx_bytes');
            $networkRxMetric->setValue($this->generateNetworkValue($i));
            $networkRxMetric->setUnit('bytes/s');
            $networkRxMetric->setRecordedAt($timestamp);
            $networkRxMetric->setAlertLevel('normal');
            
            $metrics[] = $networkRxMetric;
            
            $networkTxMetric = new InfrastructureMetric();
            $networkTxMetric->setSource('docker');
            $networkTxMetric->setMetricName('network_tx_bytes');
            $networkTxMetric->setValue($this->generateNetworkValue($i));
            $networkTxMetric->setUnit('bytes/s');
            $networkTxMetric->setRecordedAt($timestamp);
            $networkTxMetric->setAlertLevel('normal');
            
            $metrics[] = $networkTxMetric;
        }
        
        // Clear existing sample data
        $this->entityManager->createQuery(
            'DELETE FROM App\Entity\InfrastructureMetric im WHERE im.source = :source'
        )->setParameter('source', 'docker')->execute();
        
        // Persist new metrics
        foreach ($metrics as $metric) {
            $this->entityManager->persist($metric);
        }
        
        $this->entityManager->flush();
        
        $io->success("Generated {$dataPoints} data points for each metric type");
        $io->writeln("Metrics available:");
        $io->listing([
            'CPU Usage (cpu_percent)',
            'Memory Usage (memory_percent)', 
            'Network RX (network_rx_bytes)',
            'Network TX (network_tx_bytes)'
        ]);
        
        $io->note('You can now view real-time charts at http://localhost:5173');
        
        return Command::SUCCESS;
    }
    
    private function generateCPUValue(int $index, int $total): float
    {
        // Generate realistic CPU values with some patterns
        $baseValue = 45; // Base CPU around 45%
        $timePattern = sin(($index / $total) * 2 * M_PI) * 15; // Sine wave pattern
        $randomNoise = (random_int(-10, 10) / 10) * 5; // Random noise
        
        $value = $baseValue + $timePattern + $randomNoise;
        return max(5, min(95, round($value, 2))); // Clamp between 5-95%
    }
    
    private function generateMemoryValue(int $index, int $total): float
    {
        // Generate memory values that tend to be more stable
        $baseValue = 65; // Base memory around 65%
        $drift = ($index / $total) * 10; // Slight upward drift
        $randomNoise = (random_int(-5, 5) / 10) * 3; // Less random noise
        
        $value = $baseValue + $drift + $randomNoise;
        return max(30, min(90, round($value, 2))); // Clamp between 30-90%
    }
    
    private function generateNetworkValue(int $index): float
    {
        // Generate network throughput values
        $baseValue = 1024 * 1024; // 1 MB/s base
        $spike = random_int(0, 100) < 10 ? random_int(1, 5) : 1; // 10% chance of spike
        $randomMultiplier = random_int(50, 200) / 100; // 0.5x to 2x multiplier
        
        $value = $baseValue * $spike * $randomMultiplier;
        return round($value, 0);
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