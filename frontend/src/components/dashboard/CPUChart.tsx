import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { BarChart3 } from 'lucide-react'

const CPUChart = () => {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Container CPU Usage</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="h-64 flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 rounded-lg">
          <div className="text-center">
            <BarChart3 className="h-12 w-12 text-blue-400 mx-auto mb-4" />
            <p className="text-gray-600">CPU usage chart coming soon</p>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

export default CPUChart