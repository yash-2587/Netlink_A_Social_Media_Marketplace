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
$encryption_key = 'daku-and-netlink'; // Replace with a secure key
// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'fetchMessages') {
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
    
        if ($receiver_id) {
            $stmt = $conn->prepare("SELECT sender_id, receiver_id, message, is_image, is_video, 
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
            foreach ($messages as &$message) {
                if ($message['is_image']) {
                    $decrypted_path = decrypt_message($message['message'], $encryption_key);
                    $message['message'] = $decrypted_path && file_exists($decrypted_path) ? $decrypted_path : '[Image not available]';
                } elseif ($message['is_video']) {
                    $decrypted_path = decrypt_message($message['message'], $encryption_key);
                    $message['message'] = $decrypted_path && file_exists($decrypted_path) ? $decrypted_path : '[Video not available]';
                } else {
                    $decrypted_message = decrypt_message($message['message'], $encryption_key);
                    $message['message'] = $decrypted_message ? $decrypted_message : '[Message not available]';
                }
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
            // $encryption_key = 'daku-and-netlink'; // Replace with a secure key
            $encrypted_message = encrypt_message($message, $encryption_key);

            // Corrected SQL INSERT statement
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, timestamp, is_image) 
                                    VALUES (:sender_id, :receiver_id, :message, :timestamp, 0)");
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

    // Ensure no debug logs are printed for successful image requests
    if ($action === 'sendImage') {
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $timestamp = date('Y-m-d H:i:s'); // This will now use the correct timezone

        if ($receiver_id && isset($_FILES['image'])) {
            $image = $_FILES['image'];
            $upload_dir = 'uploads/image-messages/';

            // Ensure the uploads directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Create the directory with proper permissions
            }

            $image_path = $upload_dir . uniqid() . '_' . basename($image['name']);

            if (move_uploaded_file($image['tmp_name'], $image_path)) {
                // Encrypt the image path before saving
                // $encryption_key = 'daku-and-netlink'; // Ensure this key matches the one used in fetchMessages
                $encrypted_image_path = encrypt_message($image_path, $encryption_key);

                $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, timestamp, is_image) 
                                       VALUES (:sender_id, :receiver_id, :message, :timestamp, 1)");
                $stmt->execute([
                    ':sender_id' => $user_id,
                    ':receiver_id' => $receiver_id,
                    ':message' => $encrypted_image_path,
                    ':timestamp' => $timestamp
                ]);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'timestamp' => $timestamp, 'image_path' => $image_path]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to upload image']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
        }
        exit;
    }

    if ($action === 'sendVideo') {
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $timestamp = date('Y-m-d H:i:s'); // This will now use the correct timezone
    
        if ($receiver_id && isset($_FILES['video'])) {
            $video = $_FILES['video'];
            $upload_dir = 'uploads/video-messages/';
    
            // Ensure the uploads directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Create the directory with proper permissions
            }
    
            $video_path = $upload_dir . uniqid() . '_' . basename($video['name']);
    
            if (move_uploaded_file($video['tmp_name'], $video_path)) {
                // Encrypt the video path before saving
                $encrypted_video_path = encrypt_message($video_path, $encryption_key);
    
                $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, timestamp, is_image, is_video) 
                                       VALUES (:sender_id, :receiver_id, :message, :timestamp, 0, 1)");
                $stmt->execute([
                    ':sender_id' => $user_id,
                    ':receiver_id' => $receiver_id,
                    ':message' => $encrypted_video_path,
                    ':timestamp' => $timestamp
                ]);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'timestamp' => $timestamp, 'video_path' => $video_path]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to upload video']);
            }
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
                            <a href="group_chats.php" class="btn btn-outline-primary w-100 mb-2">Group Chats</a>
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
                                            onclick="updateChat('<?php echo htmlspecialchars($user['display_name']); ?>', <?php echo htmlspecialchars($user['id']); ?>)">
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
                        <div id="chat-messages"></div> <!-- Ensure this exists -->
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
                            <input type="file" id="image-input" class="form-control" accept="image/*" style="display: none;">
                            <button class="btn btn-secondary" id="image-btn" type="button">
                                <i class="bi bi-image"></i>
                                Image
                            </button>
                            <input type="file" id="video-input" class="form-control" accept="video/*" style="display: none;">
                            <button class="btn btn-secondary" id="video-btn" type="button">
                                <i class="bi bi-camera-video"></i>
                                Video
                            </button>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="receiver_id">
            </div>
        </div>
    </div>
    <script>
    const POLLING_INTERVAL = 3000; // Poll every 3 minutes
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
        console.log('User selected:', username, 'with ID:', userId); // Debugging log
        activeReceiverId = userId;

        // Clear the chat box
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.innerHTML = ''; // Clear the message box

        // Update the chat header
        document.getElementById('chat-header').textContent = 'Chat with ' + username;
        document.getElementById('receiver_id').value = userId; // Ensure this is set

        // Fetch messages and scroll to the last message
        await fetchMessageHistory(userId);

        // Scroll to the last message
        scrollToBottom();

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

            if (!response.ok) {
                console.error('Failed to fetch messages:', response.statusText);
                return;
            }

            const messages = await response.json();
            if (!Array.isArray(messages)) {
                console.error('Invalid response format:', messages);
                return;
            }

            const chatMessages = document.getElementById('chat-messages');
            chatMessages.innerHTML = ''; // Clear the message box

            // Debugging: Log messages to verify the response
            console.log('Fetched messages:', messages);

            // Render messages in reverse order
            messages.reverse().forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.classList.add('message', 
                    parseInt(msg.sender_id) === parseInt(currentUserId) ? 'sent' : 'received'
                );

                const timestamp = new Date(msg.timestamp).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                if (msg.is_image) {
                    messageDiv.innerHTML = `
                        <div class="message-content">
                            <img src="${escapeHtml(msg.message)}" alt="Image" class="chat-image">
                            <small class="timestamp">${timestamp}</small>
                        </div>
                    `;
                } else if (msg.is_video) {
                    messageDiv.innerHTML = `
                        <div class="message-content">
                            <video controls class="chat-video">
                                <source src="${escapeHtml(msg.message)}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                            <small class="timestamp">${timestamp}</small>
                        </div>
                    `;
                } else {
                    const safeMessage = msg.message ? escapeHtml(msg.message) : '[Message not available]';
                    messageDiv.innerHTML = `
                        <div class="message-content">
                            <p>${safeMessage}</p>
                            <small class="timestamp">${timestamp}</small>
                        </div>
                    `;
                }

                chatMessages.prepend(messageDiv); // Prepend messages to extend upwards
            });
        } catch (error) {
            console.error('Error fetching message history:', error);
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
                scrollToBottom(); // Ensure the latest message is visible
            } else {
                console.error('Error sending message:', result.error);
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    }

    async function sendImage() {
        const imageInput = document.getElementById('image-input');
        const receiverId = document.getElementById('receiver_id').value;

        if (!imageInput.files.length || !receiverId) {
            console.error('Image or Receiver ID is missing.');
            return; // Ensure both image and receiver ID are present
        }
        const formData = new FormData();
        formData.append('action', 'sendImage');
        formData.append('receiver_id', receiverId);
        formData.append('image', imageInput.files[0]);

        try {
            const response = await fetch('messages.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                // Immediately fetch new messages
                await fetchMessageHistory(receiverId);
                // Clear input
                imageInput.value = '';
                scrollToBottom(); // Ensure the latest message is visible
            } else {
                console.error('Error sending image:', result.error);
            }
        } catch (error) {
            console.error('Error sending image:', error);
        }
    }

    async function sendVideo() {
        const videoInput = document.getElementById('video-input');
        const receiverId = document.getElementById('receiver_id').value;

        if (!videoInput.files.length || !receiverId) {
            console.error('Video or Receiver ID is missing.');
            return; // Ensure both video and receiver ID are present
        }
        const formData = new FormData();
        formData.append('action', 'sendVideo');
        formData.append('receiver_id', receiverId);
        formData.append('video', videoInput.files[0]);

        try {
            const response = await fetch('messages.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                // Immediately fetch new messages
                await fetchMessageHistory(receiverId);
                // Clear input
                videoInput.value = '';
                scrollToBottom(); // Ensure the latest message is visible
            } else {
                console.error('Error sending video:', result.error);
            }
        } catch (error) {
            console.error('Error sending video:', error);
        }
    }

    document.getElementById('send-btn').addEventListener('click', sendMessage);
    document.getElementById('image-btn').addEventListener('click', () => {
        document.getElementById('image-input').click();
    });
    document.getElementById('image-input').addEventListener('change', sendImage);
    document.getElementById('video-btn').addEventListener('click', () => {
        document.getElementById('video-input').click();
    });
    document.getElementById('video-input').addEventListener('change', sendVideo);

    document.getElementById('chat-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            sendMessage();
            e.preventDefault(); // Prevent default behavior of Enter key
        }
    });

    window.addEventListener('beforeunload', () => {
        if (pollingIntervalId) {
            clearInterval(pollingIntervalId);
        }
    });

    function scrollToBottom() {
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight; // Ensure the bottom portion is visible
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;") // Fixed typo here
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    </script>
</body>
</html>