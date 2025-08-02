const WebSocket = require('ws');
const http = require('http');
const url = require('url');

// Create HTTP server
const server = http.createServer();

// Create WebSocket server
const wss = new WebSocket.Server({ server });

// Store connected clients
const clients = new Map();
const chatRooms = new Map();

// Authentication middleware
const authenticate = (token) => {
  // In a real implementation, you would verify the JWT token
  // For now, we'll just check if it exists
  return token && token.length > 0;
};

wss.on('connection', (ws, req) => {
  const { query } = url.parse(req.url, true);
  const token = query.token;

  if (!authenticate(token)) {
    ws.close(1008, 'Authentication failed');
    return;
  }

  console.log('Client connected');

  // Store client connection
  const clientId = Date.now() + Math.random();
  clients.set(clientId, {
    ws,
    token,
    chatRooms: new Set()
  });

  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);
      handleMessage(clientId, data);
    } catch (error) {
      console.error('Error parsing message:', error);
    }
  });

  ws.on('close', () => {
    console.log('Client disconnected');
    const client = clients.get(clientId);
    if (client) {
      // Leave all chat rooms
      client.chatRooms.forEach(chatId => {
        leaveChatRoom(clientId, chatId);
      });
      clients.delete(clientId);
    }
  });

  ws.on('error', (error) => {
    console.error('WebSocket error:', error);
  });
});

function handleMessage(clientId, data) {
  const client = clients.get(clientId);
  if (!client) return;

  switch (data.type) {
    case 'join.chat':
      joinChatRoom(clientId, data.chat_id);
      break;
    case 'leave.chat':
      leaveChatRoom(clientId, data.chat_id);
      break;
    case 'typing':
      broadcastTyping(data.chat_id, clientId, data.is_typing);
      break;
    default:
      console.log('Unknown message type:', data.type);
  }
}

function joinChatRoom(clientId, chatId) {
  const client = clients.get(clientId);
  if (!client) return;

  // Add client to chat room
  if (!chatRooms.has(chatId)) {
    chatRooms.set(chatId, new Set());
  }
  chatRooms.get(chatId).add(clientId);
  client.chatRooms.add(chatId);

  console.log(`Client ${clientId} joined chat ${chatId}`);
}

function leaveChatRoom(clientId, chatId) {
  const client = clients.get(clientId);
  if (!client) return;

  // Remove client from chat room
  const chatRoom = chatRooms.get(chatId);
  if (chatRoom) {
    chatRoom.delete(clientId);
    if (chatRoom.size === 0) {
      chatRooms.delete(chatId);
    }
  }
  client.chatRooms.delete(chatId);

  console.log(`Client ${clientId} left chat ${chatId}`);
}

function broadcastToChat(chatId, message, excludeClientId = null) {
  const chatRoom = chatRooms.get(chatId);
  if (!chatRoom) return;

  chatRoom.forEach(clientId => {
    if (clientId !== excludeClientId) {
      const client = clients.get(clientId);
      if (client && client.ws.readyState === WebSocket.OPEN) {
        client.ws.send(JSON.stringify(message));
      }
    }
  });
}

function broadcastTyping(chatId, clientId, isTyping) {
  const client = clients.get(clientId);
  if (!client) return;

  broadcastToChat(chatId, {
    type: isTyping ? 'user.typing' : 'user.stopped_typing',
    chat_id: chatId,
    user: {
      id: clientId,
      name: 'User' // In a real app, you'd get this from the token
    },
    is_typing: isTyping
  }, clientId);
}

// Handle Laravel broadcasting events
function handleLaravelEvent(event) {
  switch (event.event) {
    case 'App\\Events\\MessageSent':
      const messageData = JSON.parse(event.data);
      broadcastToChat(messageData.chat_id, {
        type: 'message.sent',
        ...messageData
      });
      break;
    case 'App\\Events\\UserTyping':
      const typingData = JSON.parse(event.data);
      broadcastToChat(typingData.chat_id, {
        type: typingData.is_typing ? 'user.typing' : 'user.stopped_typing',
        ...typingData
      });
      break;
  }
}

// Start server
const PORT = process.env.PORT || 6001;
server.listen(PORT, () => {
  console.log(`WebSocket server running on port ${PORT}`);
});

// Handle graceful shutdown
process.on('SIGTERM', () => {
  console.log('SIGTERM received, shutting down gracefully');
  server.close(() => {
    console.log('Server closed');
    process.exit(0);
  });
});

module.exports = { handleLaravelEvent }; 