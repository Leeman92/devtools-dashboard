import { BarChart3, Container, GitBranch, Package, Database } from 'lucide-react'

interface NavbarProps {
  activeTab: string
  setActiveTab: (tab: string) => void
}

const Navbar = ({ activeTab, setActiveTab }: NavbarProps) => {
  return (
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
          onClick={() => setActiveTab('images')}
          className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-left transition-colors ${
            activeTab === 'images' 
              ? 'bg-slate-700 text-white' 
              : 'text-slate-300 hover:bg-slate-700 hover:text-white'
          }`}
        >
          <Database className="h-5 w-5" />
          <span>Images</span>
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
  )
}

export default Navbar