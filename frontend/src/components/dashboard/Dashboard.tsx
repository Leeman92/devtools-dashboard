import { useState, useEffect } from 'react'
import { api } from '@/lib/api'
import type { DockerContainer, DockerImage } from '@/types/docker'
import StatsCards from './StatsCards'
import ContainersList from './ContainersList'
import ImagesList from './ImagesList'
import CPUChart from './CPUChart'
import MemoryChart from './MemoryChart'
import TabContent from './TabContent'

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

        {/* Monitoring Charts */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <CPUChart />
          <MemoryChart />
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