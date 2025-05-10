<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function connect_to_db()
{
    require_once 'config.php';

    $dsn = "mysql:host=" . DB_CONFIG['host'] . ";dbname=" . DB_CONFIG['db'];
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
    ];

    try {
        return new PDO($dsn, DB_CONFIG['user'], DB_CONFIG['pass'], $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int) $e->getCode());
    }
}

function upload_image_locally($file, $target_dir)
{
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    } else {
        return false;
    }
}

function process_file_and_execute_query($pdo, $file, $target_dir, $query_callback)
{
    if (empty($file['name'])) {
        return false;
    }

    $new_image_path = upload_image_locally($file, $target_dir);

    if (!$new_image_path) {
        return false;
    }

    return $query_callback($pdo, $new_image_path);
}

function create_user($pdo, $email, $phone_number, $full_name, $username, $hashed_password, $display_name, $bio)
{
    $target_dir = 'uploads/profile-pictures/';

    $query_callback = function ($pdo, $profile_picture_path) use ($username, $full_name, $email, $phone_number, $hashed_password, $display_name, $bio) {
        $username = strtolower($username);
        $sql = "INSERT INTO users_table 
                  (username, full_name, email, phone_number, password, profile_picture_path, display_name, bio) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $full_name, $email, $phone_number, $hashed_password, $profile_picture_path, $display_name, $bio]);

        return $stmt->rowCount() > 0;
    };

    return process_file_and_execute_query($pdo, $_FILES['profile_picture_picker'], $target_dir, $query_callback);
}

function get_user_by_credentials($pdo, $username, $password)
{
    $sql = "SELECT * FROM users_table WHERE username = ? OR email = ? OR phone_number = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $username, $username]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        error_log("No user found for: $username");
        return false;
    }
    if($row['status']=="suspended"){
        error_log(" $username has been suspended");
        return false;
    }
    $hashed_password = $row['password'];
    if (password_verify($password, $hashed_password)) {
        return $row;
    } else {
        error_log("Password verification failed for user: $username");
    }

    return false;
}


function get_user_info_from_username($pdo, $username)
{
    $sql = "SELECT * 
              FROM users_table 
              WHERE username = ?
                OR email = ?
                OR phone_number = ?;";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $username,
        $username,
        $username
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return false;
    }

    return $row;
}



function get_user_post_count($pdo, $user_id)
{
    $sql = "SELECT COUNT(*) AS post_count FROM posts_table WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC)['post_count'];
    return $row;
}

function get_all_users($pdo)
{
    $sql = "SELECT id, username, display_name, profile_picture_path FROM users_table";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $profiles = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $profiles[] = $row;
    }

    return $profiles;
}

function get_row_by_id($pdo, $table_name, $row_id)
{
    $sql = "SELECT * 
              FROM $table_name
              WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$row_id]);

    if (!$stmt || $stmt->rowCount() === 0) {
        return false;
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row;
}

function get_user_info($pdo, $user_id)
{
    return get_row_by_id($pdo, 'users_table', $user_id);
}

function get_users_info($pdo, $user_ids)
{
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $sql = "SELECT id, username, display_name, profile_picture_path 
            FROM users_table 
            WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($user_ids);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function update_user_profile($pdo, $user_id, $new_display_name, $new_bio)
{
    $new_image_file = $_FILES['profile_picture_picker'];
    $is_image_updated = !empty($new_image_file['name']);
    $is_display_name_updated = isset($new_display_name);
    $is_bio_updated = isset($new_bio);
    $is_profile_updated = $is_display_name_updated || $is_image_updated || $is_bio_updated;

    $new_image_url = '';

    if ($is_profile_updated) {
        $target_dir = 'uploads/profile-pictures/';

        if ($is_image_updated) {
            $new_image_url = upload_image_locally($new_image_file, $target_dir);
            $_SESSION['user_profile_picture_path'] = $new_image_url;
        }

        $sql = "UPDATE users_table SET 
            profile_picture_path = IF(:is_image_updated, :new_image_url, profile_picture_path),
            display_name = IF(:is_display_name_updated, :new_display_name, display_name),
            bio = IF(:is_bio_updated, :new_bio, bio)
            WHERE id = :user_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':is_image_updated', $is_image_updated, PDO::PARAM_BOOL);
        $stmt->bindParam(':new_image_url', $new_image_url);
        $stmt->bindParam(':is_display_name_updated', $is_display_name_updated, PDO::PARAM_BOOL);
        $stmt->bindParam(':new_display_name', $new_display_name);
        $stmt->bindParam(':is_bio_updated', $is_bio_updated, PDO::PARAM_BOOL);
        $stmt->bindParam(':new_bio', $new_bio);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }
}


function does_value_exist($pdo, $table, $column, $value)
{
    $sql = "SELECT * FROM $table WHERE $column = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$value]);
    $result = $stmt->fetchAll();
    return count($result) > 0;
}

function does_row_exist($pdo, $table, $column1, $value1, $column2, $value2)
{
    $sql = "SELECT * FROM $table WHERE $column1 = ? AND $column2 = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$value1, $value2]);
    $result = $stmt->fetchAll();
    return count($result) > 0;
}


function get_user_followers(PDO $pdo, $user_id)
{
    $sql = "SELECT u.* 
            FROM users_table u 
            INNER JOIN followers_table f ON u.id = f.follower_id
            WHERE f.followed_id = :user_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_followed_users_by_user(PDO $pdo, $user_id)
{
    $sql = "SELECT u.* 
            FROM users_table u 
            INNER JOIN followers_table f ON u.id = f.followed_id
            WHERE f.follower_id = :user_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function follow_user(PDO $pdo, $follower_id, $followed_id)
{
    $sql = "INSERT INTO followers_table (follower_id, followed_id) VALUES (:follower_id, :followed_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':follower_id', $follower_id, PDO::PARAM_INT);
    $stmt->bindParam(':followed_id', $followed_id, PDO::PARAM_INT);
    $success = $stmt->execute();
    return $success;
}

function unfollow_user(PDO $pdo, $follower_id, $followed_id)
{
    $sql = "DELETE FROM followers_table WHERE follower_id = :follower_id AND followed_id = :followed_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':follower_id', $follower_id, PDO::PARAM_INT);
    $stmt->bindParam(':followed_id', $followed_id, PDO::PARAM_INT);
    $success = $stmt->execute();
    return $success;
}

function get_user_by_username($conn, $username) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users_table WHERE username = ? OR email = ? OR phone_number = ?");
        $stmt->execute([$username, $username, $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function get_all_items($pdo)
{
    $sql = "SELECT id, name, description, price, image_path FROM items_table";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $profiles = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $profiles[] = $row;
    }

    return $profiles;
}
?>