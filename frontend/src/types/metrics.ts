export interface MetricData {
  id: number
  source: string
  metricName: string
  value: number
  unit?: string
  alertLevel?: 'normal' | 'warning' | 'critical'
  recordedAt: string
  tags?: Record<string, string>
}

export interface ChartDataPoint {
  timestamp: string
  value?: number
  avg_value?: number
  min_value?: number
  max_value?: number
}

export interface MetricsResponse {
  metrics: MetricData[]
  count: number
  timestamp: string
}

export interface ChartResponse {
  source: string
  metric_name: string
  chart_data: ChartDataPoint[]
  period_hours: number
  interval: string
  timestamp: string
}

export interface LatestMetricsResponse {
  metrics: MetricData[]
  count: number
  timestamp: string
}

export interface ContainerStats {
  id: string
  name: string
  cpu_percent: number
  memory_usage: number
  memory_limit: number
  memory_percent: number
  network_rx: number
  network_tx: number
  block_read: number
  block_write: number
}

export interface SystemMetrics {
  cpu_percent: number
  memory_percent: number
  disk_percent: number
  load_average: number[]
  uptime: number
  timestamp: string
} 