import { useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Database, Calendar, HardDrive, Tag, ChevronDown, ChevronUp } from 'lucide-react'
import type { DockerImage } from '@/types/docker'

interface ImagesListProps {
  images: DockerImage[]
  loading: boolean
  showAll?: boolean
  title?: string
  description?: string
}

const ImagesList: React.FC<ImagesListProps> = ({ 
  images, 
  loading, 
  showAll = false, 
  title = "Docker Images",
  description = "Overview of Docker images in the system"
}) => {
  const [expanded, setExpanded] = useState<string | null>(null)

  const displayedImages = showAll ? images : images.slice(0, 5)

  const formatDate = (timestamp: number): string => {
    return new Date(timestamp * 1000).toLocaleString()
  }

  const formatImageId = (id: string): string => {
    return id.replace('sha256:', '').substring(0, 12)
  }

  const getTagsDisplay = (repoTags: string[]): string[] => {
    if (!repoTags || repoTags.length === 0) {
      return ['<none>']
    }
    return repoTags
  }

  const formatBytes = (bytes: number): string => {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
  }

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Database className="h-5 w-5" />
            {title}
          </CardTitle>
          <CardDescription>{description}</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Database className="h-5 w-5" />
          {title}
          <Badge variant="secondary">{images.length}</Badge>
        </CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent className="p-0">
        {displayedImages.length === 0 ? (
          <div className="flex items-center justify-center py-8 text-gray-500">
            <Database className="h-8 w-8 mr-2" />
            No Docker images found
          </div>
        ) : (
          <div className="space-y-1">
            {displayedImages.map((image, index) => (
              <div key={image.Id || `image-${index}`} className="border-b last:border-b-0">
                <div className="px-6 py-4 hover:bg-gray-50/50 transition-colors">
                  <div className="flex items-center justify-between">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-3 mb-2">
                        <div className="flex items-center gap-2">
                          <Tag className="h-4 w-4 text-gray-400" />
                          <div className="font-medium text-sm">
                              {getTagsDisplay(image.RepoTags || []).map((tag, index) => (
                              <Badge key={index} variant="outline" className="mr-1 text-xs">
                                  {tag}
                              </Badge>
                              ))}
                          </div>
                        </div>
                      </div>
                      
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                        <div className="flex items-center gap-2">
                           <Database className="h-3 w-3" />
                           <span className="font-mono text-xs">
                             {formatImageId(image.Id || '')}
                           </span>
                         </div>
                         
                         <div className="flex items-center gap-2">
                           <HardDrive className="h-3 w-3" />
                           <span>{formatBytes(image.Size || 0)}</span>
                         </div>
                         
                         <div className="flex items-center gap-2">
                           <Calendar className="h-3 w-3" />
                           <span>{formatDate(image.Created || 0)}</span>
                         </div>
                      </div>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setExpanded(expanded === (image.Id || '') ? null : (image.Id || ''))}
                      >
                        {expanded === image.Id ? (
                          <ChevronUp className="h-4 w-4" />
                        ) : (
                          <ChevronDown className="h-4 w-4" />
                        )}
                      </Button>
                    </div>
                  </div>
                  
                  {expanded === (image.Id || '') && (
                    <div className="mt-4 pt-4 border-t bg-gray-50/50 rounded-lg p-4">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                          <h4 className="font-medium mb-2">Image Details</h4>
                           <div className="space-y-1 text-gray-600">
                             <div><strong>Virtual Size:</strong> {formatBytes(image.VirtualSize || 0)}</div>
                             <div><strong>Containers:</strong> {image.Containers === -1 ? 'N/A' : image.Containers}</div>
                             <div><strong>Parent ID:</strong> {image.ParentId || 'None'}</div>
                           </div>
                         </div>
                         
                         <div>
                           <h4 className="font-medium mb-2">Repository Info</h4>
                           <div className="space-y-1 text-gray-600">
                             <div><strong>Digests:</strong> {image.RepoDigests?.length || 0}</div>
                             <div><strong>Tags:</strong> {image.RepoTags?.length || 0}</div>
                             {image.Labels && Object.keys(image.Labels).length > 0 && (
                               <div><strong>Labels:</strong> {Object.keys(image.Labels).length}</div>
                             )}
                           </div>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
        
        {!showAll && images.length > 5 && (
          <div className="px-6 py-4 bg-gray-50/50 border-t">
            <p className="text-sm text-gray-500 text-center">
              Showing {displayedImages.length} of {images.length} images
            </p>
          </div>
        )}
      </CardContent>
    </Card>
  )
}

export default ImagesList 