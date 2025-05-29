import { Button } from '@/components/ui/button'
import { UserProfile } from '@/components/auth/UserProfile'
import Navbar from '@/components/ui/navbar'
import { Bell } from 'lucide-react'

interface LayoutProps {
  children: React.ReactNode
  activeTab: string
  setActiveTab: (tab: string) => void
}

const Layout = ({ children, activeTab, setActiveTab }: LayoutProps) => {
  return (
    <div className="min-h-screen bg-gray-50 flex">
      {/* Sidebar */}
      <div className="w-64 bg-slate-800 text-white flex flex-col">
        {/* Logo */}
        <div className="p-6">
          <h1 className="text-xl font-bold">DASHBOARD</h1>
        </div>

        {/* Navigation */}
        <Navbar activeTab={activeTab} setActiveTab={setActiveTab} />
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
          {children}
        </main>
      </div>
    </div>
  )
}

export default Layout 