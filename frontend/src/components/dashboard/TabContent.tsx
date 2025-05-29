import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { GitBranch, Package } from 'lucide-react'

interface TabContentProps {
  type: 'cicd' | 'repositories'
}

const TabContent = ({ type }: TabContentProps) => {
  if (type === 'cicd') {
    return (
      <Card>
        <CardHeader>
          <CardTitle>CI/CD Pipeline Status</CardTitle>
          <CardDescription>
            Monitor your continuous integration and deployment pipelines
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="text-center py-12">
            <GitBranch className="h-16 w-16 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">CI/CD Integration Coming Soon</h3>
            <p className="text-gray-600">
              GitHub Actions integration will be available in the next update
            </p>
          </div>
        </CardContent>
      </Card>
    )
  }

  if (type === 'repositories') {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Repository Management</CardTitle>
          <CardDescription>
            Manage your code repositories and deployment configurations
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="text-center py-12">
            <Package className="h-16 w-16 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">Repository Management Coming Soon</h3>
            <p className="text-gray-600">
              Repository integration and management features will be available soon
            </p>
          </div>
        </CardContent>
      </Card>
    )
  }

  return null
}

export default TabContent 