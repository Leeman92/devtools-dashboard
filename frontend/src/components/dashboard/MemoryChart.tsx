import { useState, useEffect } from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'
import { Activity, TrendingUp, MemoryStick } from 'lucide-react'
import { api } from '@/lib/api'
import { format } from 'date-fns'
import type { ChartDataPoint, ChartResponse } from '@/types/metrics'

interface ChartData extends ChartDataPoint {
  formattedTime: string
}

const MemoryChart = () => {
  const [chartData, setChartData] = useState<ChartData[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [currentMemory, setCurrentMemory] = useState<number>(0)

  // Generate mock memory data
  const generateMockData = (): ChartData[] => {
    const now = new Date()
    const data: ChartData[] = []
    
    for (let i = 23; i >= 0; i--) {
      const timestamp = new Date(now.getTime() - i * 5 * 60 * 1000) // 5-minute intervals
      const value = 30 + Math.random() * 50 // Memory between 30-80%
      
      data.push({
        timestamp: timestamp.toISOString(),
        value: Math.round(value * 100) / 100, // Round to 2 decimal places
        formattedTime: format(timestamp, 'HH:mm')
      })
    }
    
    return data
  }

  useEffect(() => {
    const fetchMemoryData = async () => {
      try {
        setLoading(true)
        
        // Try to fetch real data from infrastructure metrics
        try {
          const response: ChartResponse = await api.infrastructure.chartData('docker', 'memory_percent', 1)
          console.log('Memory API Response:', response) // Debug log
          
          if (response.chart_data && response.chart_data.length > 0) {
            const formattedData: ChartData[] = response.chart_data.map(point => {
              const date = new Date(point.timestamp)
              const formattedTime = format(date, 'HH:mm')
              
              // Debug timezone conversion
              console.log(`Memory Timestamp: ${point.timestamp} -> Local: ${date.toLocaleString()} -> Chart: ${formattedTime}`)
              
              return {
                timestamp: point.timestamp,
                value: point.avg_value || point.value || 0, // Use avg_value from API, fallback to value
                formattedTime: formattedTime
              }
            })
            console.log('Formatted Memory data:', formattedData) // Debug log
            setChartData(formattedData)
            setCurrentMemory(formattedData[formattedData.length - 1]?.value || 0)
            setError(null) // Clear any previous errors
          } else {
            throw new Error('No chart data available - API returned empty data')
          }
        } catch (apiError) {
          console.log('API Error:', apiError)
          throw apiError // Re-throw to be caught by outer catch
        }
        
      } catch (err) {
        console.error('Failed to fetch memory data:', err)
        const errorMessage = err instanceof Error ? err.message : 'Failed to load memory data'
        setError(`Using mock data - ${errorMessage}`)
        
        // Fall back to mock data on error
        const mockData = generateMockData()
        setChartData(mockData)
        setCurrentMemory(mockData[mockData.length - 1]?.value || 0)
      } finally {
        setLoading(false)
      }
    }

    fetchMemoryData()
    
    // Update every 30 seconds
    const interval = setInterval(fetchMemoryData, 30000)
    return () => clearInterval(interval)
  }, [])

  const formatTooltip = (value: number) => {
    return [`${value.toFixed(1)}%`]
  }

  const formatYAxisTick = (value: number) => `${value}%`

  const getMemoryStatus = (memory: number) => {
    if (memory > 85) return { color: 'text-red-600', bgColor: 'bg-red-50', status: 'Critical' }
    if (memory > 70) return { color: 'text-yellow-600', bgColor: 'bg-yellow-50', status: 'High' }
    return { color: 'text-green-600', bgColor: 'bg-green-50', status: 'Normal' }
  }

  const memoryStatus = getMemoryStatus(currentMemory)

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <MemoryStick className="h-5 w-5 text-purple-500" />
            Memory Usage
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="h-64 flex items-center justify-center">
            <div className="flex items-center gap-2 text-gray-500">
              <Activity className="h-5 w-5 animate-pulse" />
              Loading memory data...
            </div>
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <MemoryStick className="h-5 w-5 text-purple-500" />
            Memory Usage
          </div>
          <div className="flex items-center gap-2">
            <div className={`px-3 py-1 rounded-full text-sm font-medium ${memoryStatus.bgColor} ${memoryStatus.color}`}>
              {currentMemory.toFixed(1)}%
            </div>
            <div className={`px-2 py-1 rounded text-xs ${memoryStatus.bgColor} ${memoryStatus.color}`}>
              {memoryStatus.status}
            </div>
          </div>
        </CardTitle>
      </CardHeader>
      <CardContent>
        {error && (
          <div className="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div className="flex items-center gap-2 text-yellow-700 text-sm">
              <TrendingUp className="h-4 w-4" />
              Showing sample data - {error}
            </div>
          </div>
        )}
        
        <div className="h-64">
          <ResponsiveContainer width="100%" height="100%">
            <AreaChart data={chartData}>
              <defs>
                <linearGradient id="memoryGradient" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#8b5cf6" stopOpacity={0.3}/>
                  <stop offset="95%" stopColor="#8b5cf6" stopOpacity={0.1}/>
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
              <XAxis 
                dataKey="formattedTime" 
                stroke="#6b7280"
                fontSize={12}
              />
              <YAxis 
                tickFormatter={formatYAxisTick}
                stroke="#6b7280"
                fontSize={12}
                domain={[0, 100]}
              />
              <Tooltip 
                formatter={formatTooltip}
                labelStyle={{ color: '#374151' }}
                contentStyle={{ 
                  backgroundColor: '#ffffff',
                  border: '1px solid #e5e7eb',
                  borderRadius: '8px',
                  boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
                }}
              />
              <Area 
                type="monotone" 
                dataKey="value" 
                stroke="#8b5cf6" 
                strokeWidth={2}
                fill="url(#memoryGradient)"
                dot={{ fill: '#8b5cf6', strokeWidth: 2, r: 3 }}
                activeDot={{ r: 5, fill: '#7c3aed' }}
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>
        
        <div className="mt-4 flex items-center justify-between text-sm text-gray-500">
          <div className="flex items-center gap-2">
            <Activity className="h-4 w-4" />
            Last 1 hour (5-min intervals)
          </div>
          <div>
            Updates every 30 seconds
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

export default MemoryChart 