<?php
session_start();

require_once('../private/utility_functions.php');
require_once('../private/db_functions.php');
require_once('../private/config.php');

redirect_if_not_logged_in();

$conn = connect_to_db();
$username = $_SESSION['user_username'];
$user_id = $_SESSION['user_id'] ?? null; // Use session user_id instead of GET parameter

if (!$user_id) {
    header('Location: login.php');
    exit;
}

// Fetch all users except the logged-in user with their profile pictures
$sql = "SELECT id, username, profile_picture_path, display_name FROM users_table 
        WHERE username != :username 
        ORDER BY username ASC";
$stmt = $conn->prepare($sql);
$stmt->execute([':username' => $username]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current user's details
$profile_pic_compression_settings = "w_500/f_auto,q_auto:eco";
$profile_pic_transformed_url = add_transformation_parameters(
    $_SESSION['user_profile_picture_path'], 
    $profile_pic_compression_settings
);
$user_profile_link = 'user_profile.php?user_id=' . urlencode($user_id);

// CSRF Protection
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NetLink Messaging Interface">
    <title>Messages - NetLink</title>
    <link rel="icon" type="image/x-icon" href="logo.png">

    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/chat-styles.css">

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://unpkg.com/just-validate@latest/dist/just-validate.production.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/minisearch@6.1.0/dist/umd/index.min.js"></script>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script type="module" src="scripts/follow-handler.js" defer></script>
    <script type="module" src="scripts/chat-handler.js" defer></script>
    
</head>
<body data-user-id="<?php echo htmlspecialchars($user_id); ?>" 
      data-csrf-token="<?php echo htmlspecialchars($csrf_token); ?>">

    <?php include('header.php'); ?>
<<<<<<< HEAD

=======
    
>>>>>>> b580f41 (Initial commit)
    <div class="container-fluid">
        <div class="row" id="chat-layout">
            <!-- Message Sidebar -->
            <div class="col-md-3 col-lg-2 p-0" id="message-sidebar">
                <nav class="sidebar navbar navbar-light bg-white h-100 border-end">
                    <div class="d-flex flex-column h-100 w-100">
                        <!-- Current User Profile -->
                        <div class="sidebar-header p-3 border-bottom">
                            <a href="<?php echo htmlspecialchars($user_profile_link); ?>" 
                               class="text-decoration-none">
                                <div class="d-flex align-items-center">
                                    <img class="rounded-circle me-2" 
                                         src="<?php echo htmlspecialchars($profile_pic_transformed_url); ?>"
                                         alt="Profile picture" 
                                         width="40" 
                                         height="40">
                                    <div>
                                        <p class="fw-bold mb-0">
                                            <?php echo htmlspecialchars($_SESSION['user_display_name']); ?>
                                        </p>
                                        <small class="text-muted">
                                            @<?php echo htmlspecialchars($username); ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- User Search -->
                        <div class="p-3 border-bottom">
                            <input type="search" 
                                   class="form-control" 
                                   id="user-search" 
                                   placeholder="Search users...">
                        </div>

                        <!-- Users List -->
                        <div class="user-list flex-grow-1 overflow-auto">
                            <div class="list-group list-group-flush" id="users-container">
                                <?php foreach ($users as $user): ?>
                                    <button type="button" 
                                            class="list-group-item list-group-item-action user-chat-item"
                                            onclick="updateChat('<?php echo htmlspecialchars($user['username']); ?>', 
                                                              <?php echo htmlspecialchars($user['id']); ?>)">
                                        <div class="d-flex align-items-center">
                                            <img class="rounded-circle me-2" 
                                                 src="<?php echo htmlspecialchars(add_transformation_parameters(
                                                     $user['profile_picture_path'], 
                                                     'w_40,h_40,c_fill'
                                                 )); ?>"
                                                 alt="<?php echo htmlspecialchars($user['username']); ?>'s profile picture"
                                                 width="32" 
                                                 height="32">
                                            <div>
                                                <div class="fw-bold">
                                                    <?php echo htmlspecialchars($user['display_name']); ?>
                                                </div>
                                                <small class="text-muted">
                                                    @<?php echo htmlspecialchars($user['username']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Chat Interface -->
            <div class="col-md-9 col-lg-10 p-0" id="chat-ui">
                <div class="chat-container d-flex flex-column h-100">
                    <!-- Chat Header -->
                    <div class="chat-header p-3 border-bottom">
                        <h3 id="chat-header" class="mb-0">Select a conversation</h3>
                    </div>

                    <!-- Messages Container -->
                    <div id="chat-box" class="flex-grow-1 overflow-auto p-3">
                        <div id="chat-messages"></div>                       
                    </div>

                    <!-- Message Input -->
                    <div class="chat-input-container p-3 border-top">
                        <div class="input-group">
                            <input type="text" 
                                   id="chat-input" 
                                   class="form-control" 
                                   placeholder="Type a message..."
                                   aria-label="Type a message">
                            <button class="btn btn-primary" 
                                    id="send-btn" 
                                    type="button">
                                <i class="bi bi-send"></i>
                                Send
                            </button>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="receiver_id">
            </div>
        </div>
    </div>

    <script>
    // Initialize variables at the top
    const currentUserId = '<?php echo htmlspecialchars($_SESSION['user_id']); ?>';
    const currentUsername = '<?php echo htmlspecialchars($_SESSION['user_username']); ?>';
    let activeReceiverId = null;
    
    // Initialize socket connection with CORS settings
    const socket = io('http://localhost:3030', {
        withCredentials: true,
        transports: ['websocket'],
        cors: {
            origin: "http://localhost:8080",
            credentials: true
        }
    });

    // Listen for incoming messages
    socket.on('receiveMessage', (data) => {
        const chatMessages = document.getElementById('chat-messages');
        // Only add message if it's part of the current active conversation
        if ((data.sender === currentUsername && data.receiver_id === activeReceiverId) ||
            (data.receiver === currentUsername && data.sender_id === activeReceiverId)) {
            
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message', 
                data.sender === currentUsername ? 'sent' : 'received'
            );
            
            const timestamp = new Date(data.timestamp).toLocaleTimeString([], { 
                hour: '2-digit', 
                minute: '2-digit' 
            });

            messageDiv.innerHTML = `
                <div class="message-content">
                    <p>${escapeHtml(data.message)}</p>
                    <small class="timestamp">${timestamp}</small>
                </div>
            `;

            chatMessages.appendChild(messageDiv);
            scrollToBottom();
        }
    });

    // User search functionality
    document.getElementById('user-search').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const userItems = document.getElementsByClassName('user-chat-item');
        
        Array.from(userItems).forEach(item => {
            const username = item.querySelector('small').textContent.toLowerCase();
            const displayName = item.querySelector('.fw-bold').textContent.toLowerCase();
            const shouldShow = username.includes(searchTerm) || displayName.includes(searchTerm);
            item.style.display = shouldShow ? 'block' : 'none';
        });
    });

    // Update chat function
    async function updateChat(username, userId) {
        if (!userId) return;
        
        activeReceiverId = userId;
        document.getElementById('chat-header').textContent = 'Chat with ' + username;
        document.getElementById('receiver_id').value = userId;
        
        // Update active state in user list
        const userItems = document.getElementsByClassName('user-chat-item');
        Array.from(userItems).forEach(item => {
            item.classList.remove('active');
            if (item.onclick.toString().includes(userId)) {
                item.classList.add('active');
            }
        });
        
        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('receiver', username);
        history.pushState({}, '', url);
        
        // Clear existing messages
        document.getElementById('chat-messages').innerHTML = '';
        
        // Fetch message history
        await fetchMessageHistory(userId);
    }

<<<<<<< HEAD
    async function fetchMessageHistory(receiverId) {
        try {
            if (!receiverId) {
                console.error('No receiver ID provided');
                return;
            }

            const { fetchMessages } = await import('./scripts/request-utils.js');
            const csrfToken = document.body.dataset.csrfToken;
            
            if (!csrfToken) {
                console.error('No CSRF token found');
                return;
            }

            const response = await fetchMessages(receiverId, csrfToken);
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.innerHTML = '';
            
            // Handle error response
            if (response && response.error) {
                console.error('Error:', response.error);
                // Optionally redirect to login if unauthorized
                if (response.error === 'Unauthorized') {
                    window.location.href = 'login.php';
                }
                return;
            }
            
            // Handle successful response
            if (Array.isArray(response)) {
                response.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.classList.add('message', 
                        parseInt(msg.sender_id) === parseInt(currentUserId) ? 'sent' : 'received'
                    );
                    
                    const timestamp = new Date(msg.timestamp).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    messageDiv.innerHTML = `
                        <div class="message-content">
                            <p>${escapeHtml(msg.message)}</p>
                            <small class="timestamp">${timestamp}</small>
                        </div>
                    `;

                    chatMessages.appendChild(messageDiv);
                });
                
                scrollToBottom();
            } else {
                console.error('Invalid message history format:', response);
            }
        } catch (error) {
            console.error('Error fetching message history:', error);
        }
    }
=======
   // Update the fetchMessageHistory function
   async function fetchMessageHistory(receiverId) {
    try {
        if (!receiverId) {
            console.error('No receiver ID provided');
            return;
        }

        const csrfToken = document.body.dataset.csrfToken;
        if (!csrfToken) {
            console.error('No CSRF token found');
            return;
        }

        // Call execute_file.php with filename=fetch_message.php
        const response = await fetch(`/execute_file.php?filename=fetch_message.php&receiver_id=${receiverId}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
        });

        // Log the raw response for debugging
        const responseText = await response.text();
        console.log('Raw Response:', responseText);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        // Try to parse the response as JSON
        let messages;
        try {
            messages = JSON.parse(responseText);
        } catch (error) {
            console.error('Failed to parse JSON:', error);
            throw new Error('Invalid JSON response');
        }

        // Clear existing messages
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.innerHTML = '';

        // Display messages
        messages.forEach(msg => {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message', 
                parseInt(msg.sender_id) === parseInt(currentUserId) ? 'sent' : 'received'
            );

            const timestamp = new Date(msg.timestamp).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });

            messageDiv.innerHTML = `
                <div class="message-content">
                    <p>${escapeHtml(msg.message)}</p>
                    <small class="timestamp">${timestamp}</small>
                </div>
            `;

            chatMessages.appendChild(messageDiv);
        });

        scrollToBottom();
    } catch (error) {
        console.error('Error fetching message history:', error);
    }
}
>>>>>>> b580f41 (Initial commit)

    async function sendMessage() {
        const chatInput = document.getElementById('chat-input');
        const message = chatInput.value.trim();
        const receiverId = document.getElementById('receiver_id').value;
        
        if (!message || !receiverId) return;

        try {
            const receiverUsername = document.querySelector('.user-chat-item.active small')
                                           .textContent.replace('@', '');

            // Emit the message through socket
            socket.emit('sendMessage', {
                sender: currentUsername,
                sender_id: currentUserId,
                receiver: receiverUsername,
                receiver_id: receiverId,
                message: message,
                timestamp: new Date().toISOString()
            });

            // Clear input
            chatInput.value = '';
            
            // Focus back on input
            chatInput.focus();
        } catch (error) {
            console.error('Error sending message:', error);
        }
    }
<<<<<<< HEAD
=======
    
>>>>>>> b580f41 (Initial commit)

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Helper function to scroll chat to bottom
    function scrollToBottom() {
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Add event listener for send button and enter key
    document.getElementById('send-btn').addEventListener('click', sendMessage);
    document.getElementById('chat-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Attach functions to the global ChatHandler object
    window.ChatHandler = window.ChatHandler || {};
    window.ChatHandler.loadMessageHistory = fetchMessageHistory;
    </script>
</body>
</html>