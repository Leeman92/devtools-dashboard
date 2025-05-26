import { useState, useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Container, Server, Activity, Clock } from 'lucide-react'

interface DockerContainer {
  id: string
  name: string
  image: string
  status: string
  created: string
}

function App() {
  const [containers, setContainers] = useState<DockerContainer[]>([])
  const [loading, setLoading] = useState(true)
  const [isDark, setIsDark] = useState(false)

  useEffect(() => {
    // Fetch containers from the API
    const fetchContainers = async () => {
      try {
        const response = await fetch('/api/docker/containers')
        const data = await response.json()
        setContainers(data.containers || [])
      } catch (error) {
        console.error('Failed to fetch containers:', error)
      } finally {
        setLoading(false)
      }
    }

    fetchContainers()
    
    // Set up polling for real-time updates
    const interval = setInterval(fetchContainers, 5000)
    return () => clearInterval(interval)
  }, [])

  const toggleTheme = () => {
    setIsDark(!isDark)
    document.documentElement.classList.toggle('dark')
  }

  return (
    <div className={`min-h-screen bg-background ${isDark ? 'dark' : ''}`}>
      {/* Header */}
      <header className="border-b bg-card">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <Server className="h-8 w-8 text-primary" />
              <h1 className="text-2xl font-bold">DevTools Dashboard</h1>
            </div>
            <div className="flex items-center space-x-4">
              <Button variant="outline" onClick={toggleTheme}>
                {isDark ? '☀️' : '🌙'}
              </Button>
              <div className="flex items-center space-x-2 text-sm text-muted-foreground">
                <Activity className="h-4 w-4" />
                <span>Live</span>
              </div>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-8">
        {/* Stats Overview */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Containers</CardTitle>
              <Container className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{containers.length}</div>
              <p className="text-xs text-muted-foreground">
                {containers.filter(c => c.status.includes('running')).length} running
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Running Services</CardTitle>
              <Activity className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {containers.filter(c => c.status.includes('running')).length}
              </div>
              <p className="text-xs text-muted-foreground">
                Active services
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">System Status</CardTitle>
              <Server className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-green-600">Healthy</div>
              <p className="text-xs text-muted-foreground">
                All systems operational
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Last Updated</CardTitle>
              <Clock className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {new Date().toLocaleTimeString()}
              </div>
              <p className="text-xs text-muted-foreground">
                Auto-refresh every 5s
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Containers List */}
        <Card>
          <CardHeader>
            <CardTitle>Docker Containers</CardTitle>
            <CardDescription>
              Real-time view of your Docker containers
            </CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="flex items-center justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
              </div>
            ) : containers.length === 0 ? (
              <div className="text-center py-8 text-muted-foreground">
                No containers found
              </div>
            ) : (
              <div className="space-y-4">
                {containers.map((container) => (
                  <div
                    key={container.id}
                    className="flex items-center justify-between p-4 border rounded-lg"
                  >
                    <div className="flex items-center space-x-4">
                      <div className={`w-3 h-3 rounded-full ${
                        container.status.includes('running') 
                          ? 'bg-green-500' 
                          : 'bg-red-500'
                      }`} />
                      <div>
                        <h3 className="font-medium">{container.name}</h3>
                        <p className="text-sm text-muted-foreground">
                          {container.image}
                        </p>
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="text-sm font-medium capitalize">
                        {container.status}
                      </div>
                      <div className="text-xs text-muted-foreground">
                        {new Date(container.created).toLocaleDateString()}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </main>
    </div>
  )
}

export default App
