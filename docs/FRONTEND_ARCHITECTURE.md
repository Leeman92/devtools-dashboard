# Frontend Architecture Documentation

This document outlines the architecture, component structure, and development patterns for the DevTools Dashboard frontend application.

## Overview

The frontend is a modern React TypeScript application built with Vite, featuring:
- **Framework**: React 18+ with TypeScript
- **Build Tool**: Vite for fast development and optimized production builds
- **Styling**: Tailwind CSS with shadcn/ui components
- **State Management**: React hooks (useState, useEffect)
- **Authentication**: JWT-based with context provider
- **Real-time Updates**: Polling-based container monitoring

## Project Structure

```
frontend/
├── src/
│   ├── components/           # Reusable UI components
│   │   ├── auth/            # Authentication components
│   │   ├── dashboard/       # Dashboard-specific components
│   │   ├── layout/          # Layout and navigation components
│   │   └── ui/              # Base UI components (shadcn/ui)
│   ├── hooks/               # Custom React hooks
│   ├── lib/                 # Utility functions and API client
│   ├── types/               # TypeScript type definitions
│   └── App.tsx              # Root application component
├── public/                  # Static assets
├── dist/                    # Production build output
└── docs/                    # Component documentation
```

## Component Architecture

### Component Hierarchy

```
App.tsx (Root)
├── AuthProvider (Context)
├── ProtectedRoute (Auth Guard)
└── Layout (Main Structure)
    ├── Navbar (Sidebar Navigation)
    └── Dashboard (Content Router)
        ├── StatsCards (Overview Metrics)
        ├── ContainersList (Docker Management)
        ├── CPUChart (Performance Visualization)
        └── TabContent (Feature Placeholders)
```

### Core Components

#### `App.tsx`
- **Purpose**: Application root and state orchestration
- **Responsibilities**: 
  - Global state management (activeTab)
  - Authentication context setup
  - Route protection enforcement
- **State**: `activeTab: string`
- **Dependencies**: AuthProvider, ProtectedRoute, Layout, Dashboard

#### `Layout.tsx`
- **Purpose**: Application shell with sidebar and header
- **Responsibilities**:
  - Sidebar navigation structure
  - Header with notifications and user profile
  - Main content area layout
  - Responsive design implementation
- **Props**: `children: ReactNode`, `activeTab: string`, `setActiveTab: (tab: string) => void`

#### `Navbar.tsx`
- **Purpose**: Sidebar navigation with tab switching
- **Responsibilities**:
  - Tab navigation (Dashboard, Containers, CI/CD, Repositories)
  - Active tab visual feedback
  - Consistent navigation UX
- **Props**: `activeTab: string`, `setActiveTab: (tab: string) => void`

### Dashboard Components

#### `Dashboard.tsx`
- **Purpose**: Main content controller and data orchestration
- **Responsibilities**:
  - Docker container data fetching
  - Real-time polling management (5-second intervals)
  - Tab-based content routing
  - Error handling and loading states
- **Props**: `activeTab: string`
- **State**: `containers: DockerContainer[]`, `loading: boolean`
- **API Integration**: Docker containers endpoint

#### `StatsCards.tsx`
- **Purpose**: Key metrics visualization
- **Responsibilities**:
  - Running containers count display
  - CI/CD status indicator
  - Recent commits counter
  - Visual statistics with gradient cards
- **Props**: `runningContainers: number`, `recentCommits: number`
- **Styling**: Gradient backgrounds, responsive grid layout

#### `ContainersList.tsx`
- **Purpose**: Docker container management interface
- **Responsibilities**:
  - Recent containers view (dashboard)
  - Complete containers list (containers tab)
  - Loading and empty state handling
  - Container status visualization
- **Props**: 
  - `containers: DockerContainer[]`
  - `loading: boolean`
  - `showAll?: boolean`
  - `title?: string`
  - `description?: string`
- **Features**: Sorting, filtering, status indicators

#### `CPUChart.tsx`
- **Purpose**: Performance monitoring visualization
- **Current State**: Placeholder component
- **Future Features**: Real-time CPU/memory charts with Recharts
- **Props**: None (will expand for chart data)

#### `TabContent.tsx`
- **Purpose**: Future feature placeholders
- **Responsibilities**: CI/CD and Repository management interfaces
- **Props**: `type: 'cicd' | 'repositories'`
- **Future Integration**: GitHub Actions, repository management

## Type Definitions

### `types/docker.ts`
```typescript
export interface DockerContainer {
  id: string           // Container unique identifier
  name: string         // Container name (display)
  image: string        // Docker image name and tag
  status: string       // Container status string
  state: string        // Container state (running/exited)
  created: string      // Creation timestamp
}
```

### Future Type Additions
```typescript
// Planned type definitions
export interface ContainerStats {
  cpu: number
  memory: number
  network: NetworkStats
}

export interface CICDPipeline {
  id: string
  name: string
  status: 'success' | 'failure' | 'running'
  lastRun: string
}
```

## Development Patterns

### Component Design Principles
1. **Single Responsibility**: Each component has one clear purpose
2. **Props Interface**: Explicit TypeScript interfaces for all props
3. **Composition Over Inheritance**: Favor component composition
4. **State Locality**: Keep state as close to usage as possible
5. **Performance**: Efficient re-renders with proper dependencies

### State Management Strategy
- **Local State**: `useState` for component-specific data
- **Effect Management**: `useEffect` for side effects and data fetching
- **Context Pattern**: `AuthProvider` for global authentication state
- **Props Drilling**: Minimal with strategic component composition

### Styling Guidelines
- **Tailwind CSS**: Utility-first approach for consistent styling
- **shadcn/ui**: Pre-built accessible components
- **Responsive Design**: Mobile-first with responsive breakpoints
- **Design Tokens**: Consistent colors, spacing, and typography
- **Dark Mode Ready**: Prepared for future dark mode support

### API Integration
- **Centralized Client**: `/lib/api.ts` for all API calls
- **Error Handling**: Consistent error boundaries and user feedback
- **Loading States**: Proper loading indicators and skeleton screens
- **Real-time Updates**: Polling-based updates with cleanup

## Performance Considerations

### Current Optimizations
- **Vite Build System**: Fast development and optimized production builds
- **Code Splitting**: Prepared for lazy loading implementation
- **Bundle Analysis**: Regular bundle size monitoring
- **TypeScript**: Strict typing for development efficiency

### Future Optimizations
- **React.memo**: For expensive component renders
- **useMemo/useCallback**: For computed values and event handlers
- **Lazy Loading**: For tab content and large components
- **Virtual Scrolling**: For large container lists

## Testing Strategy

### Current Setup
- **TypeScript**: Compile-time error checking
- **Build Validation**: Production build verification
- **Linting**: ESLint configuration for code quality

### Planned Testing
- **Unit Tests**: Jest + React Testing Library
- **Integration Tests**: API integration testing
- **E2E Tests**: Playwright for user workflows
- **Visual Regression**: Component visual testing

## Development Workflow

### Local Development
```bash
# Start development environment
./scripts/dev.sh

# Frontend-only development
./scripts/docker-node.sh dev

# Type checking
./scripts/docker-node.sh npx tsc --noEmit

# Production build
./scripts/docker-node.sh build
```

### Code Quality
- **TypeScript**: Strict mode enabled
- **ESLint**: Configured for React and TypeScript
- **Prettier**: Code formatting (planned)
- **Pre-commit Hooks**: Validation before commits

## Future Roadmap

### Immediate Enhancements (Next Sprint)
1. **Container Actions**: Start/stop/restart functionality
2. **Real-time Charts**: CPU/memory usage with Recharts
3. **WebSocket Integration**: Real-time updates without polling
4. **Enhanced Error Handling**: Better error boundaries and user feedback

### Medium-term Features (2-3 Sprints)
1. **Container Logs Viewer**: Real-time log streaming
2. **GitHub Actions Integration**: CI/CD pipeline monitoring
3. **Repository Management**: Repository configuration interface
4. **Dark Mode**: Theme switching capability

### Long-term Vision (Future Releases)
1. **Multi-host Support**: Multiple Docker daemon management
2. **Advanced Monitoring**: Comprehensive metrics dashboard
3. **Alert System**: Configurable notifications
4. **Plugin Architecture**: Extensible component system

## Contributing Guidelines

### Adding New Components
1. Create component in appropriate directory structure
2. Define TypeScript interfaces for all props
3. Follow naming conventions (PascalCase for components)
4. Include JSDoc comments for complex logic
5. Export as default from component file
6. Update this documentation

### Code Review Checklist
- [ ] TypeScript interfaces defined
- [ ] Responsive design implemented
- [ ] Error states handled
- [ ] Loading states included
- [ ] Accessibility considerations
- [ ] Performance implications reviewed

## References

- [React Documentation](https://react.dev)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [shadcn/ui Components](https://ui.shadcn.com)
- [Vite Documentation](https://vitejs.dev/guide/) 