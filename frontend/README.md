# DevTools Dashboard - Frontend

A modern React TypeScript dashboard for monitoring Docker containers and CI/CD pipelines with beautiful, responsive design.

## 🎨 Features

- **Modern React 18** with TypeScript and strict type checking
- **Beautiful UI** with Tailwind CSS v3.4.0 and gradient cards
- **Responsive Design** that works on desktop, tablet, and mobile
- **Real-time Updates** with automatic data refresh every 5 seconds
- **Professional Navigation** with sidebar and multiple dashboard sections
- **Accessible Components** using shadcn/ui component library
- **Fast Development** with Vite hot module replacement
- **Docker-First Workflow** - no local Node.js installation required

## 🚀 Tech Stack

### Core Framework
- **React 18** - Modern functional components with hooks
- **TypeScript** - Strict type checking and enhanced developer experience
- **Vite 6.3.5** - Lightning-fast development server and optimized builds

### Styling & UI
- **Tailwind CSS v3.4.0** - Utility-first CSS framework
- **shadcn/ui** - Accessible, customizable component library
- **Lucide React** - Beautiful, consistent icon library
- **CSS Grid & Flexbox** - Modern layout techniques

### Development Tools
- **ESLint** - Code linting and style enforcement
- **TypeScript Compiler** - Type checking and compilation
- **Vite Dev Server** - Hot module replacement and fast builds
- **Docker Integration** - Containerized development environment

## 🏗️ Architecture

### Component Structure
```
frontend/src/
├── components/
│   ├── ui/              # shadcn/ui base components
│   │   ├── button.tsx   # Button component
│   │   ├── card.tsx     # Card components
│   │   └── avatar.tsx   # Avatar component
│   ├── dashboard/       # Dashboard-specific components
│   └── layout/          # Layout components (Header, Sidebar)
├── hooks/               # Custom React hooks
├── lib/                 # Utility functions and configurations
├── types/               # TypeScript type definitions
├── App.tsx              # Main application component
└── main.tsx             # Application entry point
```

### Design System

#### Colors
- **Primary Blue**: `#3b82f6` - Used for container status and primary actions
- **Success Green**: `#10b981` - Used for running services and success states
- **Warning Orange**: `#fb923c` - Used for warnings and recent activity
- **Background**: `#f8fafc` - Light gray background for main content
- **Sidebar**: `#1e293b` - Dark slate for navigation sidebar

#### Typography
- **Headings**: System font stack with proper font weights
- **Body Text**: Consistent text sizing with Tailwind's type scale
- **Code**: Monospace font for technical information

#### Spacing
- **Grid System**: 6px base unit (gap-6, p-6, mb-8)
- **Card Padding**: Consistent 24px (p-6) for all cards
- **Section Spacing**: 32px (space-y-8) between major sections

#### Components
- **Cards**: Rounded corners (rounded-xl) with subtle shadows
- **Buttons**: Consistent padding and hover states
- **Status Badges**: Color-coded with proper contrast ratios

## 🛠️ Development Workflow

### Getting Started
```bash
# Start development server (Docker-based, no local Node.js required)
./scripts/docker-node.sh dev

# Or start full-stack environment
./scripts/dev.sh
```

### Package Management
```bash
# Add new dependency
./scripts/docker-node.sh add <package-name>

# Add development dependency
./scripts/docker-node.sh add-dev <package-name>

# Remove package
./scripts/docker-node.sh remove <package-name>

# Install all dependencies
./scripts/docker-node.sh install

# Update dependencies
./scripts/docker-node.sh update
```

### Development Commands
```bash
# Start development server
./scripts/docker-node.sh dev

# Build for production
./scripts/docker-node.sh build

# Run linting
./scripts/docker-node.sh lint

# Run tests
./scripts/docker-node.sh test

# Type checking
./scripts/docker-node.sh type-check

# Bundle analysis
./scripts/docker-node.sh analyze

# Clean node_modules
./scripts/docker-node.sh clean
```

### Code Quality
```bash
# Check TypeScript compilation
./scripts/docker-node.sh tsc

# Run ESLint
./scripts/docker-node.sh lint

# Fix ESLint issues
./scripts/docker-node.sh lint --fix

# Security audit
./scripts/docker-node.sh audit
```

## 🎯 Component Development

### React Component Standards
```tsx
import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ComponentProps {
  title: string;
  data: DataType[];
  onUpdate?: (data: DataType[]) => void;
}

/**
 * Component description with proper JSDoc.
 */
export const MyComponent: React.FC<ComponentProps> = ({ 
  title, 
  data, 
  onUpdate 
}) => {
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // Effect logic with proper cleanup
    const cleanup = () => {
      // Cleanup logic
    };
    
    return cleanup;
  }, []);

  const handleAction = async (): Promise<void> => {
    try {
      setLoading(true);
      // Async logic
      onUpdate?.(newData);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error');
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <Card className="hover:shadow-lg transition-shadow">
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent>
        {/* Component content */}
      </CardContent>
    </Card>
  );
};
```

### TypeScript Best Practices
```tsx
// Define proper interfaces
interface ContainerData {
  id: string;
  name: string;
  status: 'running' | 'stopped' | 'error';
  image: string;
  created: string;
}

// Use proper typing for API responses
interface ApiResponse<T> {
  data: T;
  count: number;
  success: boolean;
  error?: string;
}

// Type API functions
const fetchContainers = async (): Promise<ApiResponse<ContainerData[]>> => {
  const response = await fetch('/api/docker/containers');
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  return response.json();
};
```

### Tailwind CSS Best Practices
```tsx
// Use utility classes for consistent styling
<div className="p-6 space-y-6 bg-gray-50">
  <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
  
  {/* Responsive grid */}
  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    {/* Gradient cards */}
    <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
      <h3 className="text-lg font-semibold">Containers Running</h3>
      <p className="text-3xl font-bold">{containerCount}</p>
    </div>
  </div>
  
  {/* Status badges */}
  <span className={`px-2 py-1 rounded text-sm font-medium ${
    status === 'running' 
      ? 'bg-green-100 text-green-800' 
      : 'bg-red-100 text-red-800'
  }`}>
    {status}
  </span>
</div>
```

## 🔧 Configuration

### Vite Configuration
The project uses Vite with TypeScript and includes:
- **API Proxy**: Automatically proxies `/api/*` requests to backend
- **Path Aliases**: `@/` maps to `src/` directory
- **Hot Module Replacement**: Instant updates during development
- **Optimized Builds**: Tree shaking and minification for production

### Tailwind Configuration
- **Content Paths**: Configured to scan all TypeScript/JSX files
- **Custom Colors**: Extended with project-specific color palette
- **Responsive Breakpoints**: Mobile-first responsive design
- **Plugin Support**: Ready for additional Tailwind plugins

### TypeScript Configuration
- **Strict Mode**: Enabled for better type safety
- **Path Mapping**: Configured for clean imports
- **JSX Support**: React JSX transform enabled
- **Module Resolution**: Node-style module resolution

## 🚀 Production Build

### Build Process
```bash
# Create optimized production build
./scripts/docker-node.sh build

# Analyze bundle size
./scripts/docker-node.sh analyze

# Preview production build locally
./scripts/docker-node.sh preview
```

### Build Optimization
- **Code Splitting**: Automatic route-based code splitting
- **Tree Shaking**: Removes unused code from bundles
- **Asset Optimization**: Images and fonts optimized for web
- **Gzip Compression**: Assets compressed for faster loading
- **Cache Busting**: Proper cache headers for static assets

## 🧪 Testing

### Testing Strategy
```bash
# Run unit tests
./scripts/docker-node.sh test

# Run tests in watch mode
./scripts/docker-node.sh test --watch

# Run tests with coverage
./scripts/docker-node.sh test --coverage
```

### Testing Best Practices
- **Component Testing**: Test component behavior and props
- **Hook Testing**: Test custom hooks in isolation
- **Integration Testing**: Test component interactions
- **Accessibility Testing**: Ensure WCAG compliance
- **Visual Regression**: Test UI consistency

## 🎨 Design Guidelines

### Responsive Design
- **Mobile First**: Design for mobile, enhance for desktop
- **Breakpoints**: sm (640px), md (768px), lg (1024px), xl (1280px)
- **Touch Targets**: Minimum 44px for touch interactions
- **Readable Text**: Proper contrast ratios and font sizes

### Accessibility
- **Semantic HTML**: Use proper HTML elements
- **ARIA Labels**: Provide screen reader support
- **Keyboard Navigation**: Full keyboard accessibility
- **Color Contrast**: WCAG AA compliance
- **Focus Management**: Proper focus indicators

### Performance
- **Lazy Loading**: Load components and images on demand
- **Memoization**: Use React.memo and useMemo appropriately
- **Bundle Size**: Keep bundle size under 500KB gzipped
- **Core Web Vitals**: Optimize for LCP, FID, and CLS

## 🐛 Troubleshooting

### Common Issues

#### Container Name Conflicts
```bash
# If development container conflicts
docker stop devtools-frontend-dev && docker rm devtools-frontend-dev
./scripts/docker-node.sh dev
```

#### Tailwind CSS Not Working
```bash
# Clear cache and reinstall
./scripts/docker-node.sh clean
./scripts/docker-node.sh install
```

#### TypeScript Errors
```bash
# Check TypeScript compilation
./scripts/docker-node.sh tsc

# Fix common issues
./scripts/docker-node.sh lint --fix
```

#### API Connection Issues
- Verify backend is running: `docker compose ps`
- Check API proxy configuration in `vite.config.ts`
- Ensure backend is accessible at `http://172.17.0.1:80`

## 📚 Resources

### Documentation
- [React Documentation](https://react.dev/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Vite Documentation](https://vitejs.dev/guide/)
- [shadcn/ui Components](https://ui.shadcn.com/)

### Development Tools
- [React Developer Tools](https://react.dev/learn/react-developer-tools)
- [TypeScript Playground](https://www.typescriptlang.org/play)
- [Tailwind CSS IntelliSense](https://marketplace.visualstudio.com/items?itemName=bradlc.vscode-tailwindcss)

## 🎯 Next Steps

### Planned Features
- [ ] Authentication system with login/logout
- [ ] Real-time charts with Recharts library
- [ ] Container management actions (start/stop/restart)
- [ ] WebSocket integration for live updates
- [ ] Advanced filtering and search
- [ ] Dark/light theme toggle
- [ ] Offline support with service worker
- [ ] Progressive Web App (PWA) features

### Performance Improvements
- [ ] Implement React Query for data fetching
- [ ] Add virtual scrolling for large lists
- [ ] Optimize bundle size with dynamic imports
- [ ] Add service worker for caching
- [ ] Implement proper error boundaries

### Developer Experience
- [ ] Add Storybook for component development
- [ ] Setup automated visual regression testing
- [ ] Add comprehensive unit test coverage
- [ ] Create component documentation
- [ ] Add debugging tools and error reporting
