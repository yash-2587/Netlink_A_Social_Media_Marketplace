<?php
session_start();
date_default_timezone_set('Asia/Kolkata'); 
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

function encrypt_message($message, $encryption_key) {
    $cipher = "AES-256-CBC";
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
    $encrypted_message = openssl_encrypt($message, $cipher, $encryption_key, 0, $iv);
    return base64_encode($iv . '::' . $encrypted_message);
}

function decrypt_message($encrypted_message, $encryption_key) {
    $cipher = "AES-256-CBC";
    $decoded_message = base64_decode($encrypted_message);
    if (!$decoded_message) {
        return null; // Return null if decoding fails
    }

    $parts = explode('::', $decoded_message, 2);
    if (count($parts) !== 2) {
        return null; // Return null if the format is invalid
    }

    list($iv, $encrypted_data) = $parts;
    return openssl_decrypt($encrypted_data, $cipher, $encryption_key, 0, $iv);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'fetchMessages') {
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
    
        if ($receiver_id) {
            $stmt = $conn->prepare("SELECT sender_id, receiver_id, message, 
                                          DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i:%s') as timestamp,
                                          DATE(timestamp) as message_date 
                                   FROM messages 
                                   WHERE (sender_id = :user_id1 AND receiver_id = :receiver_id1) 
                                      OR (sender_id = :receiver_id2 AND receiver_id = :user_id2)
                                   ORDER BY timestamp ASC");
            $stmt->execute([
                ':user_id1' => $user_id,
                ':receiver_id1' => $receiver_id,
                ':receiver_id2' => $receiver_id,
                ':user_id2' => $user_id
            ]);
    
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Decrypt messages before sending them to the client
            $encryption_key = 'your-secret-encryption-key'; // Replace with a secure key
            foreach ($messages as &$message) {
                $message['message'] = decrypt_message($message['message'], $encryption_key) ?? '[Message not available]';
            }
    
            header('Content-Type: application/json');
            echo json_encode($messages);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid receiver ID']);
        }
        exit;
    }

    if ($action === 'sendMessage') {
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $message = urldecode(trim($_POST['message'] ?? '')); // Decode URL-encoded message
        $timestamp = date('Y-m-d H:i:s'); // This will now use the correct timezone

        error_log("Receiver ID: " . $receiver_id); // Debugging log
        error_log("Message: " . $message); // Debugging log

        if ($receiver_id && $message) {
            // Set timezone for this connection
            $conn->exec("SET time_zone = '+05:30'"); // Adjust offset based on your timezone

            // Encrypt the message
            $encryption_key = 'your-secret-encryption-key'; // Replace with a secure key
            $encrypted_message = encrypt_message($message, $encryption_key);

            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, timestamp) 
                                   VALUES (:sender_id, :receiver_id, :message, :timestamp)");
            $stmt->execute([
                ':sender_id' => $user_id,
                ':receiver_id' => $receiver_id,
                ':message' => $encrypted_message,
                ':timestamp' => $timestamp
            ]);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'timestamp' => $timestamp]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
        }
        exit;
    }
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
</head>
<body data-user-id="<?php echo htmlspecialchars($user_id); ?>" 
      data-csrf-token="<?php echo htmlspecialchars($csrf_token); ?>">

    <?php include('header.php'); ?>
    
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
    const POLLING_INTERVAL = 3000; // Poll every 3 seconds
    let pollingIntervalId = null;
    const currentUserId = '<?php echo htmlspecialchars($_SESSION['user_id']); ?>';
    const currentUsername = '<?php echo htmlspecialchars($_SESSION['user_username']); ?>';
    let activeReceiverId = null;

    async function startPolling(receiverId) {
        // Clear any existing polling interval
        if (pollingIntervalId) {
            clearInterval(pollingIntervalId);
        }

        // Start new polling interval
        pollingIntervalId = setInterval(() => {
            fetchMessageHistory(receiverId);
        }, POLLING_INTERVAL);
    }

    async function updateChat(username, userId) {
        activeReceiverId = userId;
        document.getElementById('chat-header').textContent = 'Chat with ' + username;
        document.getElementById('receiver_id').value = userId; // Ensure this is set

        // Initial fetch
        await fetchMessageHistory(userId);
        
        // Start polling for this conversation
        startPolling(userId);
    }

    async function fetchMessageHistory(receiverId) {
        try {
            const response = await fetch('messages.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'fetchMessages',
                    receiver_id: receiverId
                })
            });

            const messages = await response.json();

            const chatMessages = document.getElementById('chat-messages');
            chatMessages.innerHTML = '';

            // Group messages by date using ISO format (YYYY-MM-DD)
            const messagesByDate = {};
            messages.forEach(msg => {
                const messageDate = msg.timestamp.split(' ')[0]; // Extract the date part (YYYY-MM-DD)
                if (!messagesByDate[messageDate]) {
                    messagesByDate[messageDate] = [];
                }
                messagesByDate[messageDate].push(msg);
            });

            // Display messages grouped by date
            Object.keys(messagesByDate).forEach(date => {
                // Add date separator
                const dateDiv = document.createElement('div');
                dateDiv.classList.add('date-separator');
                dateDiv.innerHTML = `<span>${formatMessageDate(date)}</span>`;
                chatMessages.appendChild(dateDiv);

                // Add messages for this date
                messagesByDate[date].forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.classList.add('message', 
                        parseInt(msg.sender_id) === parseInt(currentUserId) ? 'sent' : 'received'
                    );

                    const timestamp = new Date(msg.timestamp).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    // Handle null or undefined message
                    const safeMessage = msg.message ? escapeHtml(msg.message) : '[Message not available]';

                    messageDiv.innerHTML = `
                        <div class="message-content">
                            <p>${safeMessage}</p>
                            <small class="timestamp">${timestamp}</small>
                        </div>
                    `;

                    chatMessages.appendChild(messageDiv);
                });
            });

            scrollToBottom();
        } catch (error) {
            console.error('Error fetching message history:', error);
        }
    }

    // Add this helper function to format the date
    function formatMessageDate(dateString) {
        const messageDate = new Date(dateString); // `dateString` is now in ISO format (YYYY-MM-DD)
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);

        // Format date based on when the message was sent
        if (messageDate.toDateString() === today.toDateString()) {
            return 'Today';
        } else if (messageDate.toDateString() === yesterday.toDateString()) {
            return 'Yesterday';
        } else {
            return messageDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
    }

    async function sendMessage() {
        const chatInput = document.getElementById('chat-input');
        const message = chatInput.value.trim();
        const receiverId = document.getElementById('receiver_id').value;

        // console.log('Receiver ID:', receiverId); // Debugging log
        // console.log('Message:', message); // Debugging log

        if (!message || !receiverId) {
            console.error('Message or Receiver ID is missing.');
            return; // Ensure both message and receiver ID are present
        }

        try {
            const response = await fetch('messages.php', { // Corrected file name
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'sendMessage',
                    receiver_id: receiverId,
                    message: encodeURIComponent(message) // Ensure proper escaping
                })
            });

            const result = await response.json();
            if (result.success) {
                // Immediately fetch new messages
                await fetchMessageHistory(receiverId);
                
                // Clear input
                chatInput.value = '';
                chatInput.focus();
            } else {
                console.error('Error sending message:', result.error);
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function scrollToBottom() {
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    document.getElementById('send-btn').addEventListener('click', sendMessage);

    document.getElementById('chat-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault(); // Prevent default behavior of Enter key
            sendMessage();
        }
    });

    window.addEventListener('beforeunload', () => {
        if (pollingIntervalId) {
            clearInterval(pollingIntervalId);
        }
    });
    </script>
</body>
</html>