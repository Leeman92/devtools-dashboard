# DevTools Dashboard

A full-stack web application for monitoring Docker containers and CI jobs.

## Features

- Real-time Docker container monitoring
- CI job status tracking from GitHub
- Modern, responsive UI with Tailwind CSS
- RESTful API built with Laravel 10

## Tech Stack

### Backend
- Laravel 10
- PHP 8.4
- MySQL 8.0
- Docker SDK for PHP

### Frontend
- React
- Vite
- TypeScript
- Tailwind CSS
- shadcn/ui

## Prerequisites

- Docker and Docker Compose
- Node.js 20+
- PHP 8.4
- Git

## Quick Start

1. Clone the repository:
```bash
git clone <repository-url>
cd devtools-dashboard
```

2. Set up environment variables:
```bash
# Backend
cp src/backend/.env.example src/backend/.env

# Frontend
cp src/frontend/.env.example src/frontend/.env
```

3. Start the application:
```bash
docker compose up -d
```

4. Access the application:
- Frontend: http://localhost:3000
- Backend API: http://localhost:8080

## Development

### Backend
```bash
cd src/backend
composer install
php artisan key:generate
php artisan migrate
```

### Frontend
```bash
cd src/frontend
npm install
npm run dev
```

## Documentation

- [Backend Documentation](docs/backend/README.md)
- [Frontend Documentation](docs/frontend/README.md)
- [Project Rules](docs/PROJECT_RULES.md)

## Testing

### Backend
```bash
cd src/backend
php artisan test
```

### Frontend
```bash
cd src/frontend
npm test
```

## License

MIT
