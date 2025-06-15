#!/bin/bash

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVER_DIR="$SCRIPT_DIR/public/node"
ENV_FILE="$SCRIPT_DIR/.env"

# Load environment variables from .env file
if [ -f "$ENV_FILE" ]; then
    # Export variables from .env file, handling quotes and comments
    export $(grep -v '^#' "$ENV_FILE" | grep -v '^$' | xargs)
fi

# Set default values if not found in .env
WHATSAPP_PORT=${WHATSAPP_PORT:-3000}
SERVER_NAME=${APP_NAME:-"astra"}"-whatsapp-server"

# Function to check if .env file exists
check_env_file() {
    if [ ! -f "$ENV_FILE" ]; then
        echo "Warning: .env file not found at $ENV_FILE"
        echo "Using default port: $WHATSAPP_PORT"
    fi
}

# Function to check if server directory exists
check_server_dir() {
    if [ ! -d "$SERVER_DIR" ]; then
        echo "Error: Server directory not found at $SERVER_DIR"
        exit 1
    fi
}

case "$1" in
    start)
        echo "Starting WhatsApp server..."
        check_env_file
        check_server_dir
        echo "Server directory: $SERVER_DIR"
        echo "Port: $WHATSAPP_PORT"
        cd "$SERVER_DIR" && pm2 start ecosystem.config.js
        ;;
    stop)
        echo "Stopping WhatsApp server..."
        pm2 stop $SERVER_NAME
        ;;
    restart)
        echo "Restarting WhatsApp server..."
        check_env_file
        echo "Port: $WHATSAPP_PORT"
        pm2 restart $SERVER_NAME
        ;;
    status)
        echo "WhatsApp server status:"
        pm2 list | grep -i whatsapp
        ;;
    logs)
        echo "Showing WhatsApp server logs..."
        pm2 logs $SERVER_NAME --lines 50
        ;;
    monitor)
        echo "Opening PM2 monitor..."
        pm2 monit
        ;;
    test)
        echo "Testing WhatsApp server connection..."
        check_env_file
        echo "Testing connection to http://localhost:$WHATSAPP_PORT/check-status"
        curl -s "http://localhost:$WHATSAPP_PORT/check-status" | jq '.' 2>/dev/null || curl -s "http://localhost:$WHATSAPP_PORT/check-status"
        ;;
    health)
        echo "Checking WhatsApp server health..."
        check_env_file
        echo "Health check: http://localhost:$WHATSAPP_PORT/health"
        curl -s "http://localhost:$WHATSAPP_PORT/health" | jq '.' 2>/dev/null || curl -s "http://localhost:$WHATSAPP_PORT/health"
        ;;
    info)
        echo "WhatsApp Server Information:"
        echo "Script location: $SCRIPT_DIR"
        echo "Server directory: $SERVER_DIR"
        echo "Environment file: $ENV_FILE"
        echo "Port: $WHATSAPP_PORT"
        echo "Server name: $SERVER_NAME"
        echo ""
        if [ -f "$ENV_FILE" ]; then
            echo "Environment file exists: ✓"
        else
            echo "Environment file exists: ✗"
        fi
        if [ -d "$SERVER_DIR" ]; then
            echo "Server directory exists: ✓"
        else
            echo "Server directory exists: ✗"
        fi
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status|logs|monitor|test|health|info}"
        echo ""
        echo "Commands:"
        echo "  start   - Start the WhatsApp server"
        echo "  stop    - Stop the WhatsApp server"
        echo "  restart - Restart the WhatsApp server"
        echo "  status  - Show server status"
        echo "  logs    - Show server logs"
        echo "  monitor - Open PM2 monitor"
        echo "  test    - Test server connection"
        echo "  health  - Check server health endpoint"
        echo "  info    - Show configuration information"
        exit 1
        ;;
esac
