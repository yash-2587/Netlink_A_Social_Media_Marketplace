<?php
session_start();
require_once "../private/config.php";

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF validation failed.");
    }

    $user_id = intval($_POST['user_id']);

    if (isset($_POST['delete_user'])) {
        $stmt = $conn->prepare("DELETE FROM users_table WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $admin_id = $_SESSION['admin_id'];
        $event = "Deleted user with ID $user_id";
        $log_stmt = $conn->prepare("INSERT INTO security_logs (admin_id, event, user_id) VALUES (?, ?, ?)");
        $log_stmt->bind_param("isi", $admin_id, $event, $user_id);
        $log_stmt->execute();
        $log_stmt->close();
    }

    if (isset($_POST['verify_user'])) {
        $stmt = $conn->prepare("UPDATE users_table SET verification_status = 'verified' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['reject_user'])) {
        $stmt = $conn->prepare("UPDATE users_table SET verification_status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch users with follower count
$users = $conn->query("SELECT users_table.*, COUNT(followers_table.follower_id) AS follower_count 
    FROM users_table 
    LEFT JOIN followers_table ON users_table.id = followers_table.followed_id 
    GROUP BY users_table.id");

// Fetch user documents
$documents = $conn->query("SELECT user_documents.*, users_table.username 
    FROM user_documents 
    INNER JOIN users_table ON user_documents.user_id = users_table.id");

// Improved posts query with user information
$posts = [];
$posts_query = "SELECT p.*, u.username 
                FROM posts_table p 
                INNER JOIN users_table u ON p.user_id = u.id 
                ORDER BY p.created_at DESC";
$result = $conn->query($posts_query);

while ($row = $result->fetch_assoc()) {
    // Initialize the user's posts array if it doesn't exist
    if (!isset($posts[$row['user_id']])) {
        $posts[$row['user_id']] = [];
    }
    
    // Process the media path based on content type
    if ($row['is_image']) {
        $media_base_path = "../public/uploads/";
        // Check if the image is in different possible directories
        $possible_paths = [
            "items/" . $row['image_dir'],
            "image-messages/" . $row['image_dir'],
            "profile-pictures/" . $row['image_dir']
        ];
        
        $media_path = null;
        foreach ($possible_paths as $path) {
            if (file_exists($media_base_path . $path)) {
                $media_path = $media_base_path . $path;
                break;
            }
        }
        $row['processed_media'] = $media_path;
    } elseif ($row['is_video']) {
        $media_path = "../public/uploads/video-messages/" . $row['image_dir'];
        $row['processed_media'] = $media_path;
    }
    
    $posts[$row['user_id']][] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Doc Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            color: #1c1e21;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0,0,0,0.1);
        }

        h1 {
            color: #1877f2;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #1877f2;
            color: white;
        }

        button {
            background-color: #1877f2;
            color: white;
            border: none;
            padding: 8px 12px;
            margin: 2px;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #165db3;
        }

        .post-dropdown {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background: #f0f2f5;
            border-radius: 5px;
        }

        .media-preview {
            max-width: 200px;
            max-height: 200px;
            margin: 10px 0;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .post-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .post-caption {
            margin: 5px 0;
            color: #1c1e21;
        }
        .post-timestamp {
            color: #65676b;
            font-size: 0.9em;
        }
    </style>
    <script>
        function togglePosts(userId) {
            const dropdown = document.getElementById("posts-" + userId);
            dropdown.style.display = (dropdown.style.display === "none" || dropdown.style.display === "") ? "block" : "none";
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Doc Verification</h1>

    <h2>Users</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Followers</th>
            <th>Status</th>
            <th>Verification</th>
            <th>Actions</th>
        </tr>
        <?php while ($user = $users->fetch_assoc()) { ?>
            <tr>
                <td><?= $user['id']; ?></td>
                <td><?= htmlspecialchars($user['username']); ?></td>
                <td><?= htmlspecialchars($user['full_name']); ?></td>
                <td><?= htmlspecialchars($user['email']); ?></td>
                <td><?= $user['follower_count']; ?></td>
                <td><?= $user['status']; ?></td>
                <td><?= $user['verification_status']; ?></td>
                <td>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                        <button type="submit" name="verify_user">Verify</button>
                        <button type="submit" name="reject_user">Reject</button>
                        <button type="submit" name="delete_user" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                    <button onclick="togglePosts(<?= $user['id']; ?>)">View Posts</button>
                </td>
            </tr>
            <tr id="posts-<?= $user['id']; ?>" class="post-dropdown">
                <td colspan="8">
                    <strong>Posts:</strong>
                    <div class="posts-container">
                        <?php if (isset($posts[$user['id']])): 
                            foreach ($posts[$user['id']] as $post): ?>
                                <div class="post-item">
                                    <?php if ($post['is_image'] && isset($post['processed_media'])): ?>
                                        <img src="<?= htmlspecialchars($post['processed_media']); ?>" class="media-preview" alt="Post image">
                                    <?php elseif ($post['is_video'] && isset($post['processed_media'])): ?>
                                        <video controls class="media-preview">
                                            <source src="<?= htmlspecialchars($post['processed_media']); ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php endif; ?>
                                    
                                    <?php if ($post['caption']): ?>
                                        <p class="post-caption"><?= htmlspecialchars($post['caption']); ?></p>
                                    <?php endif; ?>
                                    
                                    <span class="post-timestamp">Posted on: <?= date('F j, Y, g:i a', strtotime($post['created_at'])); ?></span>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <p>No posts available.</p>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php } ?>
    </table>

    <h2>Documents</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Document</th>
            <th>Uploaded At</th>
        </tr>
        <?php while ($doc = $documents->fetch_assoc()) { ?>
            <tr>
                <td><?= $doc['id']; ?></td>
                <td><?= htmlspecialchars($doc['username']); ?></td>
                <td><a href="<?= htmlspecialchars($doc['document_path']); ?>" target="_blank">View</a></td>
                <td><?= $doc['uploaded_at']; ?></td>
            </tr>
        <?php } ?>
    </table>
</div>
</body>
</html>
