# NASOW Messaging System Setup

This guide will help you set up and run the real-time messaging system for the NASOW portal.

## Project Structure

```
NASow_project/
├── IJMIO/                    # React Frontend
│   ├── src/
│   │   ├── pages/Messages.tsx
│   │   └── services/websocket.ts
│   └── package.json
└── nasow-portal/             # Laravel Backend + WebSocket Server
    ├── app/
    │   ├── Http/Controllers/
    │   │   ├── ChatController.php
    │   │   └── MessageController.php
    │   ├── Models/
    │   │   ├── Chat.php
    │   │   └── Message.php
    │   └── Events/
    │       ├── MessageSent.php
    │       └── UserTyping.php
    ├── websocket-server.js   # WebSocket Server
    ├── package.json          # WebSocket dependencies
    ├── start-messaging.sh    # Easy startup script
    ├── stop-messaging.sh     # Easy stop script
    └── MESSAGING_SETUP.md    # This file
```

## Prerequisites

- Node.js (v16 or higher)
- PHP 8.4+ with Laravel
- MySQL database
- Composer

## Quick Start (Recommended)

### Option 1: Easy Way - Using Scripts

1. **Navigate to the Laravel backend:**
   ```bash
   cd nasow-portal
   ```

2. **Configure environment (if not done already):**
   ```bash
   cp .env.example .env
   # Edit .env with your database settings
   ```

3. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

4. **Run migrations:**
   ```bash
   php artisan migrate
   ```

5. **Start everything with one command:**
   ```bash
   ./start-messaging.sh
   ```

6. **To stop everything:**
   ```bash
   ./stop-messaging.sh
   ```

### Option 2: Manual Way

1. **Backend Setup (Laravel):**
   ```bash
   cd nasow-portal
   composer install
   cp .env.example .env
   # Configure .env file
   php artisan migrate
   npm install
   ```

2. **Start Laravel Backend:**
   ```bash
   cd nasow-portal
   php artisan serve
   ```

3. **Start WebSocket Server (new terminal):**
   ```bash
   cd nasow-portal
   npm run websocket
   ```

4. **Start React Frontend (new terminal):**
   ```bash
   cd IJMIO
   npm install
   npm start
   ```

## Access Points

Once everything is running:
- **Frontend**: http://localhost:3000
- **Backend API**: http://127.0.0.1:8000
- **WebSocket**: ws://127.0.0.1:6001

## Features

### ✅ Implemented Features

1. **Real-time Messaging**
   - Send and receive messages instantly
   - WebSocket-based real-time communication
   - Message history persistence

2. **File Attachments**
   - Upload and send files
   - Image preview support
   - Cloudinary integration for file storage

3. **Typing Indicators**
   - Real-time typing status
   - Debounced typing detection
   - Visual feedback for users

4. **Chat Management**
   - Create new conversations
   - Join/leave chat rooms
   - Role-based access control

5. **User Interface**
   - Modern, responsive design
   - Real-time connection status
   - Message read status
   - Search conversations

### 🔧 Technical Features

- **WebSocket Integration**: Real-time bidirectional communication
- **Role-based Permissions**: Different chat access based on user roles
- **File Upload**: Support for images and documents
- **Error Handling**: Comprehensive error handling and user feedback
- **Responsive Design**: Works on desktop and mobile devices

## API Endpoints

### Chat Management
- `GET /api/chats` - Get user's chats
- `POST /api/chats` - Create new chat
- `GET /api/chats/{chat}` - Get chat details
- `GET /api/chats/users/available` - Get available users

### Messaging
- `POST /api/chats/{chat}/messages` - Send message
- `POST /api/chats/{chat}/messages/read` - Mark messages as read
- `POST /api/chats/{chat}/typing` - Update typing status
- `GET /messages/{message}/attachments/{index}` - Download attachments

## WebSocket Events

### Client to Server
- `join.chat` - Join a chat room
- `leave.chat` - Leave a chat room
- `typing` - Send typing indicator

### Server to Client
- `message.sent` - New message received
- `user.typing` - User started typing
- `user.stopped_typing` - User stopped typing

## Troubleshooting

### Common Issues

1. **WebSocket Connection Failed**
   - Ensure the WebSocket server is running on port 6001
   - Check if the token is valid in localStorage
   - Verify network connectivity

2. **Messages Not Sending**
   - Check if the Laravel backend is running
   - Verify API authentication
   - Check browser console for errors

3. **File Upload Issues**
   - Ensure Cloudinary is configured in Laravel
   - Check file size limits
   - Verify file type restrictions

### Debug Mode

To enable debug logging:

1. **Frontend**: Check browser console for WebSocket logs
2. **WebSocket Server**: Logs are displayed in the terminal
3. **Laravel Backend**: Check Laravel logs in `storage/logs/laravel.log`

## Security Considerations

- All API endpoints require authentication
- WebSocket connections are authenticated via JWT tokens
- File uploads are validated and sanitized
- Role-based access control is enforced
- Messages are stored securely in the database

## Performance Optimization

- WebSocket connections are managed efficiently
- Message history is paginated
- File uploads use streaming
- Real-time updates are debounced
- Connection pooling for multiple users

## Future Enhancements

- Message encryption
- Voice/video calling
- Message reactions
- Message editing/deletion
- Group chat features
- Message search functionality
- Push notifications
- Message backup/export

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review the browser console for errors
3. Check the server logs
4. Verify all services are running correctly

---

**Note**: This is a development setup. For production deployment, additional security measures, SSL certificates, and proper server configuration will be required. 