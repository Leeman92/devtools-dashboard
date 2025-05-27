#!/bin/bash

# test-logging.sh
# Script to test logging configuration locally
# Usage: ./scripts/test-logging.sh

set -euo pipefail

echo "🧪 Testing logging configuration..."

# Start development environment
echo "📦 Starting development environment..."
./scripts/dev.sh &
DEV_PID=$!

# Wait for services to start
echo "⏳ Waiting for services to start..."
sleep 10

# Test basic health check
echo "🏥 Testing health check endpoint..."
curl -s http://localhost:8000/health || echo "❌ Health check failed"

# Test logging endpoints
echo "📝 Testing logging endpoints..."

echo "1. Testing basic logging..."
curl -s http://localhost:8000/api/test/logging || echo "❌ Basic logging test failed"

echo "2. Testing environment info..."
curl -s http://localhost:8000/api/test/env || echo "❌ Environment test failed"

echo "3. Testing error logging..."
curl -s http://localhost:8000/api/test/error || echo "❌ Error test failed (expected)"

echo "4. Testing 500 error..."
curl -s http://localhost:8000/api/test/500 || echo "❌ 500 test failed (expected)"

echo "5. Testing auth logging..."
curl -s -X POST http://localhost:8000/api/test/auth-test || echo "❌ Auth logging test failed"

# Test actual login endpoint
echo "6. Testing login endpoint with invalid data..."
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"wrongpassword"}' || echo "❌ Login test failed"

# Check logs
echo "📋 Checking container logs..."
docker logs $(docker ps --filter "name=backend" --format "{{.ID}}" | head -1) --tail 50 || echo "❌ No backend container found"

# Cleanup
echo "🧹 Cleaning up..."
kill $DEV_PID 2>/dev/null || true
docker-compose down 2>/dev/null || true

echo "✅ Logging test completed" 