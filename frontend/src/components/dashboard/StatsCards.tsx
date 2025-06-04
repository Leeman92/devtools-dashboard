import { Container, CheckCircle, Calendar, Database } from 'lucide-react'

interface StatsCardsProps {
  runningContainers: number
  recentCommits: number
  totalImages: number
}

const StatsCards: React.FC<StatsCardsProps> = ({ runningContainers, recentCommits, totalImages }) => {
  return (
    <div className="flex gap-6 mb-8">
      {/* Containers Running Card */}
      <div className="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl p-6 shadow-lg flex-1 min-w-0">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-blue-100 text-sm font-medium">Containers</p>
            <p className="text-blue-100 text-sm font-medium">Running</p>
            <p className="text-5xl font-bold mt-2">{runningContainers}</p>
          </div>
          <div className="bg-white/20 p-4 rounded-xl">
            <Container className="h-8 w-8" />
          </div>
        </div>
      </div>

      {/* CI Status Card */}
      <div className="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl p-6 shadow-lg flex-1 min-w-0">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-green-100 text-sm font-medium">CI Status</p>
            <p className="text-lg font-semibold mt-2">All systems</p>
            <p className="text-lg font-semibold">operational</p>
          </div>
          <div className="bg-white/20 p-4 rounded-xl">
            <CheckCircle className="h-8 w-8" />
          </div>
        </div>
      </div>

      {/* Recent Commits Card */}
      <div className="bg-gradient-to-br from-orange-400 to-orange-600 text-white rounded-xl p-6 shadow-lg flex-1 min-w-0">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-orange-100 text-sm font-medium">Recent</p>
            <p className="text-orange-100 text-sm font-medium">Commits</p>
            <p className="text-5xl font-bold mt-2">{recentCommits}</p>
          </div>
          <div className="bg-white/20 p-4 rounded-xl">
            <Calendar className="h-8 w-8" />
          </div>
        </div>
      </div>
      
      <div className="bg-gradient-to-br from-purple-400 to-purple-600 text-white rounded-xl p-6 shadow-lg flex-1 min-w-0">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-purple-100 text-sm font-medium">Total</p>
            <p className="text-purple-100 text-sm font-medium">Images</p>
            <p className="text-5xl font-bold mt-2">{totalImages}</p>
          </div>
          <div className="bg-white/20 p-4 rounded-xl">
            <Database className="h-8 w-8" />
          </div>
        </div>
      </div>
    </div>
  )
}

export default StatsCards 