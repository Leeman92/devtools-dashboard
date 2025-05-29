import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { DockerContainer } from '@/types/docker'

interface ContainersListProps {
  containers: DockerContainer[]
  loading: boolean
  showAll?: boolean
  title?: string
  description?: string
}

const ContainersList = ({ 
  containers, 
  loading, 
  showAll = false,
  title = "Recent Containers (4 Newest)",
  description
}: ContainersListProps) => {
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