#!/bin/bash

echo "🛑 Stopping NASOW Messaging System..."

# Function to kill process by port
kill_by_port() {
    local port=$1
    local pid=$(lsof -ti:$port)
    if [ ! -z "$pid" ]; then
        echo "🔄 Stopping service on port $port (PID: $pid)"
        kill -TERM $pid 2>/dev/null
        sleep 2
        # Force kill if still running
        if lsof -ti:$port >/dev/null 2>&1; then
            echo "⚠️  Force killing process on port $port"
            kill -KILL $(lsof -ti:$port) 2>/dev/null
        fi
        echo "✅ Service on port $port stopped"
    else
        echo "ℹ️  No service running on port $port"
    fi
}

# Stop services by port
echo "🔍 Stopping services..."
kill_by_port 8000  # Laravel
kill_by_port 6001  # WebSocket
kill_by_port 3000  # React

echo ""
echo "🧹 Cleaning up log files..."
rm -f laravel.log
rm -f websocket.log
rm -f ../IJMIO/frontend.log

echo ""
echo "✅ All services stopped successfully!"
echo "📁 Project structure is clean and ready for next run." 