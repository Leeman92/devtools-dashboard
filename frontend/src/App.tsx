import { useState, useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { AuthProvider } from '@/hooks/useAuth'
import { ProtectedRoute } from '@/components/auth/ProtectedRoute'
import { UserProfile } from '@/components/auth/UserProfile'
import { api } from '@/lib/api'
import { 
  Container, 
  GitBranch,
  Package,
  BarChart3,
  Bell,
  CheckCircle,
  Calendar
} from 'lucide-react'

interface DockerContainer {
  id: string
  name: string
  image: string
  status: string
  state: string
  created: string
}

function Dashboard() {
  const [containers, setContainers] = useState<DockerContainer[]>([])
  const [loading, setLoading] = useState(true)
  const [activeTab, setActiveTab] = useState('dashboard')

  useEffect(() => {
    // Fetch containers from the API
    const fetchContainers = async () => {
      try {
        const data = await api.docker.containers()
        setContainers(data.containers || [])
      } catch (error) {
        console.error('Failed to fetch containers:', error)
        // Don't set containers to empty array on error, keep previous data
      } finally {
        setLoading(false)
      }
    }

    fetchContainers()
    
    // Set up polling for real-time updates
    const interval = setInterval(fetchContainers, 5000)
    return () => clearInterval(interval)
  }, [])

  const runningContainers = containers.filter(c => c.state === 'running' || c.status.toLowerCase().includes('up'))
  const recentCommits = 12 // Mock data

  return (
    <div className="min-h-screen bg-gray-50 flex">
      {/* Sidebar */}
      <div className="w-64 bg-slate-800 text-white flex flex-col">
        {/* Logo */}
        <div className="p-6">
          <h1 className="text-xl font-bold">DASHBOARD</h1>
        </div>

        {/* Navigation */}
        <nav className="flex-1 px-4">
          <div className="space-y-2">
            <button
              onClick={() => setActiveTab('dashboard')}
              className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-left transition-colors ${
                activeTab === 'dashboard' 
                  ? 'bg-slate-700 text-white' 
                  : 'text-slate-300 hover:bg-slate-700 hover:text-white'
              }`}
            >
              <BarChart3 className="h-5 w-5" />
              <span>Dashboard</span>
            </button>

            <button
              onClick={() => setActiveTab('containers')}
              className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-left transition-colors ${
                activeTab === 'containers' 
                  ? 'bg-slate-700 text-white' 
                  : 'text-slate-300 hover:bg-slate-700 hover:text-white'
              }`}
            >
              <Container className="h-5 w-5" />
              <span>Containers</span>
            </button>

            <button
              onClick={() => setActiveTab('cicd')}
              className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-left transition-colors ${
                activeTab === 'cicd' 
                  ? 'bg-slate-700 text-white' 
                  : 'text-slate-300 hover:bg-slate-700 hover:text-white'
              }`}
            >
              <GitBranch className="h-5 w-5" />
              <span>CI/CD</span>
            </button>

            <button
              onClick={() => setActiveTab('repositories')}
              className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-left transition-colors ${
                activeTab === 'repositories' 
                  ? 'bg-slate-700 text-white' 
                  : 'text-slate-300 hover:bg-slate-700 hover:text-white'
              }`}
            >
              <Package className="h-5 w-5" />
              <span>Repositories</span>
            </button>
          </div>
        </nav>
      </div>

      {/* Main Content */}
      <div className="flex-1 flex flex-col">
        {/* Header */}
        <header className="bg-white border-b border-gray-200 px-6 py-4">
          <div className="flex items-center justify-between">
            <h1 className="text-2xl font-semibold text-gray-900">DevTools Dashboard</h1>
            
            <div className="flex items-center space-x-4">
              {/* Notifications */}
              <Button variant="ghost" size="sm">
                <Bell className="h-5 w-5" />
              </Button>

              {/* User Profile */}
              <UserProfile />
            </div>
          </div>
        </header>

        {/* Dashboard Content */}
        <main className="flex-1 p-6">
          {activeTab === 'dashboard' && (
            <div className="space-y-6">
              {/* Stats Cards */}
              <div className="flex gap-6 mb-8">
                {/* Containers Running Card */}
                <div className="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl p-6 shadow-lg flex-1 min-w-0">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-blue-100 text-sm font-medium">Containers</p>
                      <p className="text-blue-100 text-sm font-medium">Running</p>
                      <p className="text-5xl font-bold mt-2">{runningContainers.length}</p>
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
                <div className="bg-gradient-to-br from-orange-400 to-orange-500 text-white rounded-xl p-6 shadow-lg flex-1 min-w-0">
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
              </div>

              {/* Content Grid */}
              <div className="grid grid-cols-2 gap-6">
                {/* Docker Containers */}
                <Card>
                  <CardHeader>
                    <CardTitle>Recent Containers (4 Newest)</CardTitle>
                  </CardHeader>
                  <CardContent>
                    {loading ? (
                      <div className="flex items-center justify-center py-8">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                      </div>
                    ) : (
                      <div className="space-y-4">
                        <div className="grid grid-cols-3 gap-4 text-sm font-medium text-gray-500 border-b pb-2">
                          <div>Name</div>
                          <div>Image</div>
                          <div>Status</div>
                        </div>
                        {containers
                          .sort((a, b) => new Date(b.created).getTime() - new Date(a.created).getTime())
                          .slice(0, 4)
                          .map((container) => (
                          <div key={container.id} className="grid grid-cols-3 gap-4 items-center py-2">
                            <div className="font-medium text-gray-900">
                              {container.name.replace(/^\//, '')}
                            </div>
                            <div className="text-sm text-gray-600">
                              {container.image.split(':')[0]}
                            </div>
                            <div>
                              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                container.state === 'running'
                                  ? 'bg-green-100 text-green-800'
                                  : 'bg-red-100 text-red-800'
                              }`}>
                                {container.state === 'running' ? 'Running' : 'Exited'}
                              </span>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </CardContent>
                </Card>

                {/* Container CPU Usage Chart Placeholder */}
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
              </div>
            </div>
          )}

          {activeTab === 'containers' && (
            <Card>
              <CardHeader>
                <CardTitle>All Docker Containers</CardTitle>
                <CardDescription>
                  Complete list of Docker containers with detailed information
                </CardDescription>
              </CardHeader>
              <CardContent>
                {loading ? (
                  <div className="flex items-center justify-center py-8">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                  </div>
                ) : containers.length === 0 ? (
                  <div className="text-center py-8 text-gray-500">
                    No containers found
                  </div>
                ) : (
                  <div className="space-y-4">
                    {containers.map((container) => (
                      <div
                        key={container.id}
                        className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                      >
                        <div className="flex items-center space-x-4">
                          <div className={`w-3 h-3 rounded-full ${
                            container.state === 'running' 
                              ? 'bg-green-500' 
                              : 'bg-red-500'
                          }`} />
                          <div>
                            <h3 className="font-medium text-gray-900">
                              {container.name.replace(/^\//, '')}
                            </h3>
                            <p className="text-sm text-gray-600">
                              {container.image}
                            </p>
                          </div>
                        </div>
                        <div className="text-right">
                          <div className={`text-sm font-medium ${
                            container.state === 'running'
                              ? 'text-green-600'
                              : 'text-red-600'
                          }`}>
                            {container.state === 'running' ? 'Running' : 'Exited'}
                          </div>
                          <div className="text-xs text-gray-500">
                            {new Date(container.created).toLocaleDateString()}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          )}

          {activeTab === 'cicd' && (
            <Card>
              <CardHeader>
                <CardTitle>CI/CD Pipeline Status</CardTitle>
                <CardDescription>
                  Monitor your continuous integration and deployment pipelines
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-12">
                  <GitBranch className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                  <h3 className="text-lg font-medium text-gray-900 mb-2">CI/CD Integration Coming Soon</h3>
                  <p className="text-gray-600">
                    GitHub Actions integration will be available in the next update
                  </p>
                </div>
              </CardContent>
            </Card>
          )}

          {activeTab === 'repositories' && (
            <Card>
              <CardHeader>
                <CardTitle>Repository Management</CardTitle>
                <CardDescription>
                  Manage your code repositories and deployment configurations
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-12">
                  <Package className="h-16 w-16 text-gray-400 mx-auto mb-4" />
                  <h3 className="text-lg font-medium text-gray-900 mb-2">Repository Management Coming Soon</h3>
                  <p className="text-gray-600">
                    Repository integration and management features will be available soon
                  </p>
                </div>
              </CardContent>
            </Card>
          )}
        </main>
      </div>
    </div>
  )
}

function App() {
  return (
    <AuthProvider>
      <ProtectedRoute>
        <Dashboard />
      </ProtectedRoute>
    </AuthProvider>
  )
}

export default App
