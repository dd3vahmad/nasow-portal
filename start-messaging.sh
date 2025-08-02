#!/bin/bash

echo "🚀 Starting NASOW Messaging System..."

# Function to check if a port is in use
check_port() {
    if lsof -Pi :$1 -sTCP:LISTEN -t >/dev/null ; then
        echo "❌ Port $1 is already in use"
        return 1
    else
        echo "✅ Port $1 is available"
        return 0
    fi
}

# Check if required ports are available
echo "🔍 Checking ports..."
check_port 8000 || exit 1
check_port 6001 || exit 1
check_port 3000 || exit 1

echo ""
echo "📁 Project Structure:"
echo "├── IJMIO/ (React Frontend)"
echo "└── nasow-portal/ (Laravel Backend + WebSocket Server)"
echo ""

echo "🔧 Starting Laravel Backend..."
if [ ! -f .env ]; then
    echo "⚠️  .env file not found. Please copy .env.example to .env and configure it."
    exit 1
fi

# Start Laravel server in background
php artisan serve > laravel.log 2>&1 &
LARAVEL_PID=$!
echo "✅ Laravel server started (PID: $LARAVEL_PID)"

echo "🔧 Starting WebSocket Server..."
npm run websocket > websocket.log 2>&1 &
WEBSOCKET_PID=$!
echo "✅ WebSocket server started (PID: $WEBSOCKET_PID)"

echo ""
echo "🔧 Starting React Frontend..."
cd ../IJMIO
npm start > frontend.log 2>&1 &
FRONTEND_PID=$!
echo "✅ React frontend started (PID: $FRONTEND_PID)"

echo ""
echo "🎉 All services started successfully!"
echo ""
echo "📱 Access Points:"
echo "   Frontend: http://localhost:3000"
echo "   Backend API: http://127.0.0.1:8000"
echo "   WebSocket: ws://127.0.0.1:6001"
echo ""
echo "📋 Log Files:"
echo "   Laravel: laravel.log"
echo "   WebSocket: websocket.log"
echo "   Frontend: ../IJMIO/frontend.log"
echo ""
echo "🛑 To stop all services, run: ./stop-messaging.sh"
echo ""

# Wait for user to stop
echo "Press Ctrl+C to stop all services..."
wait 