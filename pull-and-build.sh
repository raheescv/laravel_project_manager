#!/bin/bash

# Enhanced script to pull code and optionally build
# Usage: ./pull-and-build.sh [branch-name] [--build]

set -e  # Exit on error


# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Parse arguments
BRANCH=${1:-main}
BUILD=false

if [[ "$1" == "--build" ]] || [[ "$2" == "--build" ]]; then
    BUILD=true
    if [[ "$1" == "--build" ]]; then
        BRANCH=${2:-main}
    fi
fi

# Project directory
PROJECT_DIR="/var/www/html/spa.astraqatar.com"

echo -e "${BLUE}📥 Pulling code from GitHub...${NC}"
echo -e "${YELLOW}Branch: ${BRANCH}${NC}"
if [ "$BUILD" = true ]; then
    echo -e "${YELLOW}Build: Enabled${NC}"
fi
echo ""

# Change to project directory
cd "$PROJECT_DIR" || exit 1

# Check if we're in a git repository
if [ ! -d .git ]; then
    echo -e "${RED}❌ Error: Not a git repository${NC}"
    exit 1
fi

# Show current status
echo -e "${BLUE}Current branch:${NC}"
git branch --show-current
echo ""

# Set environment to bypass SSH passphrase prompt
export GIT_SSH_COMMAND="ssh -o StrictHostKeyChecking=no -o BatchMode=yes"
export GIT_TERMINAL_PROMPT=0

# Fetch latest changes
echo -e "${BLUE}🔄 Fetching latest changes from GitHub...${NC}"
git fetch origin

# Pull the code
echo -e "${BLUE}📥 Pulling code from origin/${BRANCH}...${NC}"
if git pull origin "$BRANCH"; then
    echo -e "${GREEN}✅ Successfully pulled code from GitHub!${NC}"
else
    echo -e "${RED}❌ Failed to pull code. Please check for conflicts.${NC}"
    exit 1
fi

# Show latest commit
echo ""
echo -e "${BLUE}📝 Latest commit:${NC}"
git log -1 --oneline

# Build if requested
if [ "$BUILD" = true ]; then
    echo ""
    echo -e "${BLUE}📦 Installing npm dependencies...${NC}"
    npm install

    echo -e "${BLUE}🔨 Building assets...${NC}"
    npm run build

    echo -e "${BLUE}🚀 Optimizing Laravel...${NC}"
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo -e "${GREEN}✅ Build completed!${NC}"
fi

echo ""
echo -e "${GREEN}✅ Done!${NC}"

