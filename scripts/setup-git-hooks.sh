#!/bin/bash

# DevTools Dashboard - Git Hooks Setup
# Installs and configures git hooks for automatic validation

set -e

echo "🔧 Setting up Git hooks for DevTools Dashboard..."

# Change to project root
cd "$(dirname "$0")/.."

# Create .git/hooks directory if it doesn't exist
mkdir -p .git/hooks

# Copy pre-commit hook
echo "📋 Installing pre-commit hook..."
cp .githooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

# Configure git to use our hooks directory for future hooks
echo "⚙️  Configuring git hooks directory..."
git config core.hooksPath .githooks

# Make all hooks executable
echo "🔐 Making hooks executable..."
chmod +x .githooks/*

# Test the pre-commit hook
echo "🧪 Testing pre-commit hook..."
if ./.githooks/pre-commit; then
    echo "✅ Pre-commit hook test passed!"
else
    echo "⚠️  Pre-commit hook test failed - this is normal if setup is incomplete"
fi

echo ""
echo "🎉 Git hooks setup completed!"
echo ""
echo "📝 What was configured:"
echo "  ✅ Pre-commit hook installed"
echo "  ✅ Git configured to use .githooks directory"
echo "  ✅ All hooks made executable"
echo ""
echo "🔍 The pre-commit hook will now automatically:"
echo "  • Validate setup and dependencies"
echo "  • Check composer files are in sync"
echo "  • Verify Docker socket configuration"
echo "  • Ensure environment variables are documented"
echo "  • Check API endpoint documentation"
echo "  • Validate .cursorrules best practices"
echo "  • Test Docker build with staged changes"
echo ""
echo "💡 To bypass the hook (not recommended):"
echo "   git commit --no-verify"
echo ""
echo "🔧 To manually run the validation:"
echo "   ./.githooks/pre-commit" 