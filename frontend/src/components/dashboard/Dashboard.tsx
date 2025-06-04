import { useState, useEffect, lazy, Suspense } from 'react'
import { api } from '@/lib/api'
import type { DockerContainer, DockerImage } from '@/types/docker'
import StatsCards from './StatsCards'
import ContainersList from './ContainersList'
import ImagesList from './ImagesList'
import TabContent from './TabContent'

// Lazy load chart components to reduce initial bundle size
const CPUChart = lazy(() => import('./CPUChart'))
const MemoryChart = lazy(() => import('./MemoryChart'))

// Chart loading fallback component
const ChartLoading = () => (
  <div className="bg-white rounded-lg border p-6 animate-pulse">
    <div className="flex items-center justify-between mb-4">
      <div className="flex items-center gap-2">
        <div className="w-5 h-5 bg-gray-200 rounded"></div>
        <div className="w-24 h-5 bg-gray-200 rounded"></div>
      </div>
      <div className="w-16 h-6 bg-gray-200 rounded-full"></div>
    </div>
    <div className="h-64 bg-gray-100 rounded"></div>
  </div>
)

interface DashboardProps {
  activeTab: string
}

const Dashboard = ({ activeTab }: DashboardProps) => {
  const [containers, setContainers] = useState<DockerContainer[]>([])
  const [images, setImages] = useState<DockerImage[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    // Fetch both containers and images
    const fetchData = async () => {
      try {
        const [containersData, imagesData] = await Promise.all([
          api.docker.containers(),
          api.docker.images()
        ])
        
        setContainers(containersData?.containers || [])
        setImages(imagesData?.images || [])
      } catch (error) {
        console.error('Failed to fetch Docker data:', error)
        // Don't reset data on error, keep previous data
      } finally {
        setLoading(false)
      }
    }

    fetchData()
    
    // Set up polling for real-time updates (both containers and images)
    const interval = setInterval(fetchData, 5000)
    return () => clearInterval(interval)
  }, [])

  // Fetch data function for manual refresh
  const fetchData = async () => {
    try {
      const [containersData, imagesData] = await Promise.all([
        api.docker.containers(),
        api.docker.images()
      ])
      
      setContainers(containersData?.containers || [])
      setImages(imagesData?.images || [])
    } catch (error) {
      console.error('Failed to fetch Docker data:', error)
    }
  }

  const runningContainers = containers?.filter(c => c.state === 'running' || c.status.toLowerCase().includes('up')) || []
  const recentCommits = 12 // Mock data
  const totalImages = images?.length || 0

  if (activeTab === 'dashboard') {
    return (
      <div className="space-y-6">
        {/* Stats Cards */}
        <StatsCards 
          runningContainers={runningContainers.length}
          recentCommits={recentCommits}
          totalImages={totalImages}
        />

        {/* Monitoring Charts - Lazy Loaded */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Suspense fallback={<ChartLoading />}>
            <CPUChart />
          </Suspense>
          <Suspense fallback={<ChartLoading />}>
            <MemoryChart />
          </Suspense>
        </div>

        {/* Content Grid */}
        <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
          {/* Docker Containers */}
          <ContainersList 
            containers={containers}
            loading={loading}
            showAll={false}
            onContainerAction={fetchData}
          />
          
          {/* Docker Images */}
          <ImagesList 
            images={images}
            loading={loading}
            showAll={false}
          />
        </div>
      </div>
    )
  }

  if (activeTab === 'containers') {
    return (
      <ContainersList 
        containers={containers}
        loading={loading}
        showAll={true}
        title="All Docker Containers"
        description="Complete list of Docker containers with detailed information"
        onContainerAction={fetchData}
      />
    )
  }

  if (activeTab === 'cicd') {
    return <TabContent type="cicd" />
  }

  if (activeTab === 'images') {
    return (
      <ImagesList 
        images={images}
        loading={loading}
        showAll={true}
        title="All Docker Images"
        description="Complete list of Docker images with detailed information"
      />
    )
  }

  if (activeTab === 'repositories') {
    return <TabContent type="repositories" />
  }

  return null
}

export default Dashboard 