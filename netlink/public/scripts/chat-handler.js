const ChatHandler = {
    activeChatUserId: null,
    socket: null,
    lastMessageTimestamp: null,

    init() {
        this.initializeSocket();
        this.setupEventListeners();
    },

    initializeSocket() {
        this.socket = io('http://localhost:3030', {
            transports: ['websocket'],
            upgrade: false
        });

        this.socket.on('connect', () => {
            console.log('Connected to chat server');
        });

        this.socket.on('receiveMessage', (data) => {
            this.handleIncomingMessage(data);
        });

        this.socket.on('connect_error', (error) => {
            console.error('Socket connection error:', error);
        });
    },

    setupEventListeners() {
        const sendButton = document.getElementById('send-btn');
        const chatInput = document.getElementById('chat-input');

        sendButton.addEventListener('click', () => this.sendMessage());
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.sendMessage();
            }
        });
    },

    async sendMessage() {
        const chatInput = document.getElementById('chat-input');
        const message = chatInput.value.trim();
        const receiverId = document.getElementById('receiver_id').value;

        if (!message || !receiverId) return;

        try {
            const currentUserId = document.body.dataset.userId;
            const csrfToken = document.body.dataset.csrfToken;

            // Send via WebSocket
            this.socket.emit('sendMessage', {
                sender_id: currentUserId,
                receiver_id: receiverId,
                message: message,
                timestamp: new Date().toISOString()
            });

            // Also send via HTTP for persistence
            const response = await fetch('/execute_file.php?filename=send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `receiver_id=${receiverId}&message=${encodeURIComponent(message)}&csrf_token=${csrfToken}`
            });

            const result = await response.json();
            
            if (result.success) {
                chatInput.value = '';
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    },

    handleIncomingMessage(data) {
        const currentUserId = document.body.dataset.userId;
        const receiverId = document.getElementById('receiver_id').value;

        if ((data.sender_id === receiverId && data.receiver_id === currentUserId) ||
            (data.sender_id === currentUserId && data.receiver_id === receiverId)) {
            
            this.appendMessage({
                message: data.message,
                is_sent: data.sender_id === currentUserId,
                timestamp: data.timestamp
            });
            this.scrollToBottom();
        }
    },

    appendMessage(messageData) {
        const chatMessages = document.getElementById('chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', messageData.is_sent ? 'sent' : 'received');

        messageDiv.innerHTML = `
            <div class="message-content">
                <p>${this.escapeHtml(messageData.message)}</p>
                <small class="timestamp">${this.formatTimestamp(messageData.timestamp)}</small>
            </div>
        `;

        chatMessages.appendChild(messageDiv);
    },

    formatTimestamp(timestamp) {
        return new Date(timestamp).toLocaleTimeString([], { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    },

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    },

    scrollToBottom() {
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
};

// Initialize ChatHandler when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    ChatHandler.init();
});

// Export for global access
window.ChatHandler = ChatHandler;