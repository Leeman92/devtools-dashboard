import { useState, useEffect } from 'react'
import { api } from '@/lib/api'
import type { DockerContainer } from '@/types/docker'
import StatsCards from './StatsCards'
import ContainersList from './ContainersList'
import CPUChart from './CPUChart'
import MemoryChart from './MemoryChart'
import TabContent from './TabContent'

interface DashboardProps {
  activeTab: string
}

const Dashboard = ({ activeTab }: DashboardProps) => {
  const [containers, setContainers] = useState<DockerContainer[]>([])
  const [loading, setLoading] = useState(true)

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

  // Fetch containers function for manual refresh
  const fetchContainers = async () => {
    try {
      const data = await api.docker.containers()
      setContainers(data.containers || [])
    } catch (error) {
      console.error('Failed to fetch containers:', error)
    }
  }

  const runningContainers = containers.filter(c => c.state === 'running' || c.status.toLowerCase().includes('up'))
  const recentCommits = 12 // Mock data

  if (activeTab === 'dashboard') {
    return (
      <div className="space-y-6">
        {/* Stats Cards */}
        <StatsCards 
          runningContainers={runningContainers.length}
          recentCommits={recentCommits}
        />

        {/* Monitoring Charts */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <CPUChart />
          <MemoryChart />
        </div>

        {/* Content Grid */}
        <div className="grid grid-cols-1 gap-6">
          {/* Docker Containers */}
          <ContainersList 
            containers={containers}
            loading={loading}
            showAll={false}
            onContainerAction={fetchContainers}
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
        onContainerAction={fetchContainers}
      />
    )
  }

  if (activeTab === 'cicd') {
    return <TabContent type="cicd" />
  }

  if (activeTab === 'repositories') {
    return <TabContent type="repositories" />
  }

  return null
}

export default Dashboard 