import { useState } from 'react'
import { AuthProvider } from '@/hooks/useAuth'
import { ProtectedRoute } from '@/components/auth/ProtectedRoute'
import Layout from '@/components/layout/Layout'
import Dashboard from '@/components/dashboard/Dashboard'

function App() {
  const [activeTab, setActiveTab] = useState('dashboard')

  return (
    <AuthProvider>
      <ProtectedRoute>
        <Layout activeTab={activeTab} setActiveTab={setActiveTab}>
          <Dashboard activeTab={activeTab} />
        </Layout>
      </ProtectedRoute>
    </AuthProvider>
  )
}

export default App
