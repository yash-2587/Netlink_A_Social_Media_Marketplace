

const socket = io();
const messageInput = document.getElementById('chat-input');
const sendButton = document.getElementById('send-btn');
const chatMessages = document.getElementById('chat-messages');
let selectedUser = null;

// Fetch users from the database
// async function fetchUsers() {
//     const response = await fetch('/users');
//     const { success, users } = await response.json();
//     if (success) {
//         userListElement.innerHTML = '';
//         users.forEach((user) => {
//             if (user.username !== username) {
//                 const li = document.createElement('li');
//                 li.innerText = user.username;
//                 li.dataset.id = user.id;
//                 li.addEventListener('click', () => selectUser(user.username, user.id));
//                 userListElement.appendChild(li);
//             }
//         });
//     }
// }
// Modify existing script with these improvements

// Fetch message history when selecting a user
async function fetchMessageHistory(selectedUsername) {
    try {
        const response = await fetch(`/messages?sender=${username}&receiver=${selectedUsername}`);
        const { success, messages } = await response.json();
        
        if (success) {
            // Clear existing messages
            chatMessages.innerHTML = '';
            
            // Render historical messages
            messages.forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.classList.add('message');
                messageDiv.classList.add(
                    msg.sender === username ? 'outgoing' : 'incoming'
                );
                messageDiv.innerText = `${msg.sender}: ${msg.message}`;
                chatMessages.appendChild(messageDiv);
            });

            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    } catch (error) {
        console.error('Failed to fetch message history', error);
    }
}

// Modify selectUser function
function selectUser(user, userId) {
    selectedUser = user;
    chatWithElement.textContent = user;
    chatMessages.innerHTML = ''; // Clear existing messages
    // Fetch message history for selected user
    fetchMessageHistory(user);
}

// Modify addMessageToUI to prevent duplicates
function addMessageToUI(data, isOwnMessage) {
    // Check if message is for current conversation
    if (selectedUser === data.sender || 
        (selectedUser === data.receiver && data.sender === username)) {
        
        // Prevent duplicate messages
        const existingMessages = Array.from(chatMessages.children);
        const isDuplicate = existingMessages.some(msg => 
            msg.innerText === `${data.sender}: ${data.message}`
        );

        if (!isDuplicate) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message');
            messageDiv.classList.add(isOwnMessage ? 'outgoing' : 'incoming');
            messageDiv.innerText = `${data.sender}: ${data.message}`;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
}

// Select a user to chat with
// function selectUser(user, userId) {
//     selectedUser = user;
//     chatWithElement.textContent = user;
//     chatMessages.innerHTML = ''; // Clear the chat when switching users
// }

// Add messages to the UI
// function addMessageToUI(data, isOwnMessage) {
//     if (selectedUser === data.sender || (selectedUser === data.receiver && data.sender === username)) {
//         const messageDiv = document.createElement('div');
//         messageDiv.classList.add('message');
//         messageDiv.classList.add(isOwnMessage ? 'outgoing' : 'incoming');
//         messageDiv.innerText = `${data.sender}: ${data.message}`;
//         chatMessages.appendChild(messageDiv);
//         chatMessages.scrollTop = chatMessages.scrollHeight;
//     }
// }

// Send message
sendButton.addEventListener('click', () => {
    const message = messageInput.value.trim();
    if (message && selectedUser) {
        const data = { sender: username, receiver: selectedUser, message };
        socket.emit('sendMessage', data);
        addMessageToUI(data, true);
        messageInput.value = '';
    } else {
        alert('Please select a user to chat with.');
    }
});

// Handle Enter key to send a message
messageInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendButton.click();
    }
});

// Receive messages
socket.on('receiveMessage', (data) => {
    addMessageToUI(data, data.sender === username);
});

// Fetch user list on load
fetchUsers();