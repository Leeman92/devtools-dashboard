import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Play, Square, RotateCcw, Loader2 } from 'lucide-react'
import { useState } from 'react'
import type { DockerContainer } from '@/types/docker'
import { api } from '@/lib/api'

interface ContainersListProps {
  containers: DockerContainer[]
  loading: boolean
  showAll?: boolean
  title?: string
  description?: string
  onContainerAction?: () => void
}

const ContainersList = ({ 
  containers, 
  loading, 
  showAll = false,
  title = "Recent Containers (4 Newest)",
  description,
  onContainerAction
}: ContainersListProps) => {
  const [actionLoading, setActionLoading] = useState<{ [key: string]: string }>({})
  const [actionSuccess, setActionSuccess] = useState<{ [key: string]: boolean }>({})

  const handleContainerAction = async (containerId: string, action: 'start' | 'stop' | 'restart') => {
    try {
      setActionLoading(prev => ({ ...prev, [containerId]: action }))
      
      let result;
      switch (action) {
        case 'start':
          result = await api.docker.start(containerId)
          break
        case 'stop':
          result = await api.docker.stop(containerId)
          break
        case 'restart':
          result = await api.docker.restart(containerId)
          break
      }

      // Show success feedback
      setActionSuccess(prev => ({ ...prev, [containerId]: true }))
      setTimeout(() => {
        setActionSuccess(prev => ({ ...prev, [containerId]: false }))
      }, 2000)

      // Trigger refresh of container list
      if (onContainerAction) {
        setTimeout(onContainerAction, 1000)
      }

    } catch (error) {
      console.error(`Failed to ${action} container:`, error)
      // Show error feedback (you could add error state here)
      alert(`Failed to ${action} container: ${error instanceof Error ? error.message : 'Unknown error'}`)
    } finally {
      setActionLoading(prev => ({ ...prev, [containerId]: '' }))
    }
  }

  const displayedContainers = showAll 
    ? containers 
    : containers
        .sort((a, b) => new Date(b.created).getTime() - new Date(a.created).getTime())
        .slice(0, 4)

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{title}</CardTitle>
          {description && <CardDescription>{description}</CardDescription>}
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          </div>
        </CardContent>
      </Card>
    )
  }

  if (containers.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{title}</CardTitle>
          {description && <CardDescription>{description}</CardDescription>}
        </CardHeader>
        <CardContent>
          <div className="text-center py-8 text-gray-500">
            No containers found
          </div>
        </CardContent>
      </Card>
    )
  }

  if (showAll) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{title}</CardTitle>
          {description && <CardDescription>{description}</CardDescription>}
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {displayedContainers.map((container) => (
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
                <div className="flex items-center space-x-3">
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
                  
                  {/* Action Buttons */}
                  <div className="flex space-x-2">
                    {container.state !== 'running' && (
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => handleContainerAction(container.id, 'start')}
                        disabled={!!actionLoading[container.id]}
                        className={`${actionSuccess[container.id] ? 'border-green-500 text-green-600' : ''} hover:bg-green-50 hover:border-green-300`}
                        title="Start container"
                      >
                        {actionLoading[container.id] === 'start' ? (
                          <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                          <Play className="h-4 w-4" />
                        )}
                      </Button>
                    )}
                    
                    {container.state === 'running' && (
                      <>
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => handleContainerAction(container.id, 'stop')}
                          disabled={!!actionLoading[container.id]}
                          className={`${actionSuccess[container.id] ? 'border-green-500 text-green-600' : ''} hover:bg-red-50 hover:border-red-300`}
                          title="Stop container"
                        >
                          {actionLoading[container.id] === 'stop' ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                          ) : (
                            <Square className="h-4 w-4" />
                          )}
                        </Button>
                        
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => handleContainerAction(container.id, 'restart')}
                          disabled={!!actionLoading[container.id]}
                          className={`${actionSuccess[container.id] ? 'border-green-500 text-green-600' : ''} hover:bg-blue-50 hover:border-blue-300`}
                          title="Restart container"
                        >
                          {actionLoading[container.id] === 'restart' ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                          ) : (
                            <RotateCcw className="h-4 w-4" />
                          )}
                        </Button>
                      </>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          <div className="grid grid-cols-3 gap-4 text-sm font-medium text-gray-500 border-b pb-2">
            <div>Name</div>
            <div>Image</div>
            <div>Status</div>
          </div>
          {displayedContainers.map((container) => (
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
      </CardContent>
    </Card>
  )
}

export default ContainersList 