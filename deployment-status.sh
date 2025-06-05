#!/bin/bash

# Laravel Envoy Deployment Status Script
# Usage: ./deployment-status.sh

echo "🚀 Laravel Envoy Deployment Status"
echo "=================================="

# Check if Envoy is available
if ! command -v vendor/bin/envoy &> /dev/null; then
    echo "❌ Laravel Envoy not found. Please install with: composer require laravel/envoy"
    exit 1
fi

echo "✅ Laravel Envoy is installed"

# List available tasks
echo ""
echo "📋 Available Deployment Tasks:"
echo "------------------------------"
vendor/bin/envoy task

echo ""
echo "🏥 Current System Health:"
echo "------------------------"
php artisan health:check --no-ansi

echo ""
echo "📊 Application Status:"
echo "---------------------"
php artisan about --only=environment,cache,drivers --no-ansi

echo ""
echo "💾 Recent Backups:"
echo "------------------"
if [ -d "storage/app/backups" ]; then
    ls -la storage/app/backups/ | tail -5
else
    echo "No backup directory found"
fi

echo ""
echo "🔄 Git Status:"
echo "-------------"
git status --porcelain
if [ $? -eq 0 ] && [ -z "$(git status --porcelain)" ]; then
    echo "✅ Working directory is clean"
else
    echo "⚠️  Working directory has uncommitted changes"
fi

echo ""
echo "📝 Recent Commits:"
echo "-----------------"
git log --oneline -5

echo ""
echo "🎯 Quick Deployment Commands:"
echo "-----------------------------"
echo "Development:  vendor/bin/envoy run deploy-development"
echo "Production:   vendor/bin/envoy run safe-production-deploy"
echo "Emergency:    vendor/bin/envoy run emergency-rollback"
echo "Health:       vendor/bin/envoy run complete-system-check"
echo ""
echo "For full command list, see DEPLOYMENT.md"
