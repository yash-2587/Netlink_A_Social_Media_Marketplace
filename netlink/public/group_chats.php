<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once('../private/utility_functions.php');
require_once('../private/db_functions.php');
require_once('../private/config.php');

redirect_if_not_logged_in();

$conn = connect_to_db();
$username = $_SESSION['user_username'];
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: login.php');
    exit;
}

// Fetch all users for the group member selection, excluding the logged-in user
$stmt = $conn->prepare("SELECT id, username, display_name FROM users_table WHERE id != :user_id ORDER BY username ASC");
$stmt->execute([':user_id' => $user_id]);
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'fetchGroupMessages') {
        $group_id = intval($_POST['group_id'] ?? 0);

        if ($group_id) {
            $stmt = $conn->prepare("
                SELECT g.sender_id AS user_id, u.username AS sender_name, g.message, 
                       DATE_FORMAT(g.timestamp, '%Y-%m-%d %H:%i:%s') AS timestamp
                FROM group_messages g
                INNER JOIN `groups` gr ON g.group_id = gr.id
                INNER JOIN users_table u ON g.sender_id = u.id
                WHERE g.group_id = :group_id
                ORDER BY g.timestamp ASC
            ");
            $stmt->execute([':group_id' => $group_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode($messages);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid group ID']);
        }
        exit;
    }

    if ($action === 'sendGroupMessage') {
        $group_id = intval($_POST['group_id'] ?? 0);
        $message = urldecode(trim($_POST['message'] ?? ''));
        $timestamp = date('Y-m-d H:i:s');

        if ($group_id && $message) {
            $stmt = $conn->prepare("INSERT INTO group_messages (group_id, sender_id, message, timestamp) 
                                    VALUES (:group_id, :sender_id, :message, :timestamp)");
            $stmt->execute([
                ':group_id' => $group_id,
                ':sender_id' => $user_id,
                ':message' => $message,
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

    if ($action === 'createGroup') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $members = json_decode($_POST['members'] ?? '[]', true);

        if ($name) {
            $stmt = $conn->prepare("INSERT INTO `groups` (name, creator_id, created_at) VALUES (:name, :creator_id, NOW())");
            $stmt->execute([
                ':name' => $name,
                ':creator_id' => $user_id
            ]);

            $group_id = $conn->lastInsertId();

            // Add the creator as a member of the group
            $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, added_at) VALUES (:group_id, :user_id, NOW())");
            $stmt->execute([
                ':group_id' => $group_id,
                ':user_id' => $user_id
            ]);

            // Add selected members to the group
            if (!empty($members)) {
                $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, added_at) VALUES (:group_id, :user_id, NOW())");
                foreach ($members as $member_id) {
                    $stmt->execute([
                        ':group_id' => $group_id,
                        ':user_id' => intval($member_id)
                    ]);
                }
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Group name is required']);
        }
        exit;
    }
}

// Fetch all groups the user is part of
$stmt = $conn->prepare("
    SELECT g.id, g.name 
    FROM `groups` g
    INNER JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = :user_id
    ORDER BY g.name ASC
");
$stmt->execute([':user_id' => $user_id]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <meta name="description" content="NetLink Group Chats">
    <title>Group Chats - NetLink</title>
    <link rel="icon" type="image/x-icon" href="logo.png">

    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/chat-styles.css">

    <style>
        #group-chat-layout {
            display: flex;
            width: 100%;
            height: calc(100vh - 60px);
    /* Adjust for header */
            margin-top: 60px;
        }

        #group-sidebar {
            width: 30%;
            padding: 10px;
            background-color: #f8f9fa;
            border-right: 1px solid #ccc;
        }

        #group-chat-ui {
            flex: 1; /* Take the remaining space */
            display: flex;
            flex-direction: column;
            /* overflow: hidden; */
        }

        #group-chat-box {
            height: calc(100% - 80px); /* Adjusted height to account for input container */
            /* overflow-y: auto; */
            padding: 20px;
            background-color: #f9f9f9;
            margin-bottom: 0; /* Removed margin to avoid overlap */
            flex-grow: 1;
            padding-bottom: 80px;/* Add padding for better UX */
        }

        .chat-header {
            flex-shrink: 0; /* Ensure the chat-header does not shrink */
        }

        .chat-input-container {
            flex-shrink: 0; /* Ensure the input container does not shrink */
            display: flex;
            align-items: center;
            gap: 10px; /* Add spacing between input and button */
        }

        .chat-input-container .form-control {
            flex-grow: 1; /* Ensure the input takes up available space */
        }

        .chat-input-container .btn {
            flex-shrink: 0; /* Prevent the button from shrinking */
        }

        .container-fluid.p-0 {
            padding-top: 56px; /* Add padding to prevent overlap with the main header */
        }
    </style>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body>
    <?php include('header.php'); ?>
    <div class="container-fluid p-0">
        <!-- Adjusted layout to ensure #group-chat-ui is below the header -->
        <div id="group-chat-layout" class="d-flex flex-column">
            <!-- Group Sidebar and Chat Interface -->
            <div class="d-flex flex-grow-1">
                <div id="group-sidebar" class="bg-white border-end">
                    <nav class="sidebar navbar navbar-light h-100">
                        <div class="d-flex flex-column h-100">
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
                                <p class="fw-bold mb-0">Group Chats</p>
                                <button class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                                    <i class="bi bi-plus-circle"></i> Create Group
                                </button>
                            </div>

                            <!-- Groups List -->
                            <div class="group-list flex-grow-1 ">
                                <div class="list-group list-group-flush" id="groups-container">
                                    <?php foreach ($groups as $group): ?>
                                        <button type="button" 
                                                class="list-group-item list-group-item-action group-chat-item"
                                                onclick="openGroupChat(<?php echo htmlspecialchars($group['id']); ?>, '<?php echo htmlspecialchars($group['name']); ?>')">
                                            <div class="d-flex align-items-center">
                                                <div class="fw-bold flex-grow-1">
                                                    <?php echo htmlspecialchars($group['name']); ?>
                                                </div>
                                                <i class="bi bi-chat-dots text-muted"></i>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
                <div id="group-chat-ui" class="flex-grow-1">
                    <div class="chat-container d-flex flex-column h-100">
                        <!-- Chat Header -->
                        <div class="chat-header p-3 border-bottom bg-light">
                            <div class="d-flex align-items-center">
                                <h3 id="group-chat-header" class="mb-0 flex-grow-1">Select a group</h3>
                                <button class="btn btn-secondary btn-sm" id="group-info-btn" type="button">
                                    <i class="bi bi-info-circle"></i> Info
                                </button>
                            </div>
                        </div>

                        <!-- Messages Container -->
                        <div id="group-chat-box" class="p-3 bg-white">
                            <div id="group-chat-messages" class="d-flex flex-column gap-3"></div>
                        </div>

                        <!-- Message Input -->
                        <div class="chat-input-container p-3 border-top bg-light">
                            <input type="text" 
                                   id="group-chat-input" 
                                   class="form-control" 
                                   placeholder="Type a message..." 
                                   aria-label="Type a message">
                            <button class="btn btn-primary" 
                                    id="group-send-btn" 
                                    type="button">
                                <i class="bi bi-send"> Send </i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" id="group_id">
                </div>
            </div>
        </div>
    </div>

    <!-- Create Group Modal -->
    <div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createGroupModalLabel">Create New Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createGroupForm">
                        <div class="mb-3">
                            <label for="groupName" class="form-label">Group Name</label>
                            <input type="text" class="form-control" id="groupName" placeholder="Enter group name" required>
                        </div>
                        <div class="mb-3">
                            <label for="groupDescription" class="form-label">Group Description</label>
                            <textarea class="form-control" id="groupDescription" rows="3" placeholder="Enter group description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="groupMembers" class="form-label">Select Members</label>
                            <select id="groupMembers" class="form-select" multiple>
                                <?php foreach ($all_users as $user): ?>
                                    <option value="<?php echo htmlspecialchars($user['id']); ?>">
                                        <?php echo htmlspecialchars($user['display_name'] . ' (@' . $user['username'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Create Group</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    let pollingInterval;

    function startPolling(groupId) {
        stopPolling(); // Clear any existing polling
        pollingInterval = setInterval(() => fetchGroupMessages(groupId), 5000); // Poll every 5 seconds
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    function openGroupChat(groupId, groupName) {
        document.getElementById('group-chat-header').textContent = 'Group: ' + groupName;
        document.getElementById('group_id').value = groupId;
        document.getElementById('group-chat-messages').innerHTML = '';
        fetchGroupMessages(groupId);
        startPolling(groupId); // Start polling for new messages
    }

    async function fetchGroupMessages(groupId) {
        try {
            const response = await fetch('group_chats.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'fetchGroupMessages',
                    group_id: groupId
                })
            });

            if (!response.ok) {
                console.error('Failed to fetch group messages:', response.statusText);
                return;
            }

            const messages = await response.json();
            const chatMessages = document.getElementById('group-chat-messages');
            chatMessages.innerHTML = '';

            messages.forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.classList.add('message', msg.user_id === '<?php echo $user_id; ?>' ? 'sent' : 'received');
                messageDiv.innerHTML = `
                    <div class="message-content">
                        <p><strong>${msg.sender_name}:</strong> ${msg.message}</p>
                        <small class="timestamp">${new Date(msg.timestamp).toLocaleTimeString()}</small>
                    </div>
                `;
                chatMessages.appendChild(messageDiv);
            });

            scrollToBottom();
        } catch (error) {
            console.error('Error fetching group messages:', error);
        }
    }

    async function sendGroupMessage() {
        const groupId = document.getElementById('group_id').value;
        const message = document.getElementById('group-chat-input').value.trim();

        if (!message || !groupId) {
            console.error('Message or Group ID is missing.');
            return;
        }

        try {
            const response = await fetch('group_chats.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'sendGroupMessage',
                    group_id: groupId,
                    message: encodeURIComponent(message)
                })
            });

            const result = await response.json();
            if (result.success) {
                fetchGroupMessages(groupId);
                document.getElementById('group-chat-input').value = '';
                scrollToBottom();
            } else {
                console.error('Error sending group message:', result.error);
            }
        } catch (error) {
            console.error('Error sending group message:', error);
        }
    }

    document.getElementById('group-send-btn').addEventListener('click', sendGroupMessage);

    document.getElementById('group-chat-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            sendGroupMessage();
            e.preventDefault();
        }
    });

    document.getElementById('createGroupForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const groupName = document.getElementById('groupName').value.trim();
        const groupDescription = document.getElementById('groupDescription').value.trim();
        const groupMembers = Array.from(document.getElementById('groupMembers').selectedOptions).map(option => option.value);

        if (!groupName) {
            alert('Group name is required.');
            return;
        }

        try {
            const response = await fetch('group_chats.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'createGroup',
                    name: groupName,
                    description: groupDescription,
                    members: JSON.stringify(groupMembers)
                })
            });

            const result = await response.json();
            if (result.success) {
                alert('Group created successfully!');
                location.reload();
            } else {
                alert('Error creating group: ' + result.error);
            }
        } catch (error) {
            console.error('Error creating group:', error);
            alert('An error occurred while creating the group.');
        }
    });

    function scrollToBottom() {
        const chatBox = document.getElementById('group-chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    window.addEventListener('beforeunload', stopPolling); // Stop polling when the user leaves the page
    </script>
</body>
</html>
