<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once('../private/utility_functions.php');
redirect_if_not_logged_in();

require_once('../private/db_functions.php');

$conn = connect_to_db();

$user_info = [];
$user_bio = '';
$poster_profile_picture_url = '';
$poster_profile_pic_transformed_url = '';
$user_posts_amount = 0;
$user_followers_amount = 0;
$user_following_amount = 0;
$is_logged_in_user_profile = false;
$follow_button_checked_attribute = 'checked'; 

if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $current_user_id = $_GET['user_id'];
    $is_logged_in_user_profile = intval($current_user_id) === intval($user_id);

    $user_info = get_user_info($conn, $current_user_id);
    if ($user_info) {
        $user_bio = nl2br($user_info['bio'] ?? '');
        $poster_profile_picture_url = $user_info['profile_picture_path'] ?? '';
        $poster_profile_pic_compression_settings = "w_400/f_auto,q_auto:eco";
        $poster_profile_pic_transformed_url = add_transformation_parameters($poster_profile_picture_url, $poster_profile_pic_compression_settings);

        $user_posts_amount = get_user_post_count($conn, $current_user_id);
        $user_followers = get_user_followers($conn, $current_user_id);
        $user_followers_amount = count($user_followers);
        $user_following = get_followed_users_by_user($conn, $current_user_id);
        $user_following_amount = count($user_following);

        $is_followed_by_user = does_row_exist($conn, 'followers_table', 'follower_id', $user_id, 'followed_id', $current_user_id);
        $follow_button_checked_attribute = $is_followed_by_user ? '' : 'checked';
    }
}

// Fetch items uploaded by the user
$user_items = [];
if (isset($current_user_id)) {
    $query = "SELECT * FROM items_table WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>NetLink</title>
    <link rel="icon" type="image/x-icon" href="logo.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
        </script>
    <script src="https://unpkg.com/just-validate@latest/dist/just-validate.production.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/minisearch@6.1.0/dist/umd/index.min.js"></script>

    <link rel="stylesheet" href="css/style.css">
    <script type="module" src="scripts/follow-handler.js" defer></script>
</head>

<body class="h-100 w-100 m-0 p-0 preload">

    <div class="w-100 h-100 body-container container-fluid m-0 p-0">
        <?php include('sidebar.php'); ?>
        <?php include('header.php'); ?>
        <main class="page-user-profile bg-light">
            <div class="py-5 d-flex flex-column h-100 align-items-center gap-5">
                <div class="profile-info d-flex pb-0 gap-4 align-items-center justify-content-start mb-3">
                    <img class="user-profile-profile-picture flex-shrink-0"
                        src="<?php echo $poster_profile_pic_transformed_url; ?>" alt="">
                    <div class="user-profile-text-info d-flex flex-column gap-3 w-100 p-1">
                        <div class="d-flex gap-4 align-items-center">
                            <div>
                                <p class="user-profile-display-name fs-5 fw-bold text-body m-0">
                                    <?php echo $user_info['display_name'] ?? 'Unknown User'; ?>
                                </p>
                                <p class="user-profile-username text-secondary fs-6 m-0">
                                    <?php echo '@' . ($user_info['username'] ?? 'unknown'); ?>
                                </p>
                            </div>
                            <div>
                                <?php echo $is_logged_in_user_profile ? '
                                    <a href="edit_profile.php" class="btn btn-outline-secondary" role="button">Edit Profile</a>
                                    ' : '
                                    <input type="checkbox" class="btn-check" id="user-profile-follow-button" autocomplete="off" ' . $follow_button_checked_attribute . '>
                                    <label class="btn btn-outline-primary" for="user-profile-follow-button">
                                        <span class="follow-text fw-medium">Follow</span>
                                        <span class="unfollow-text">Unfollow</span>
                                    </label>
                                '; ?>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <a id="user-profile-posts-amount"
                                class='d-flex user-profile-posts-amount-container align-items-center gap-1 text-decoration-none text-body fw-semibold'>
                                <p class="user-profile-posts-amount m-0 fs-6">
                                    <?php $user_items = [];
                                    if (isset($current_user_id)) {
                                        $query = "SELECT * FROM items_table WHERE user_id = ?";
                                        $stmt = $conn->prepare($query);
                                        $stmt->bindValue(1, $current_user_id, PDO::PARAM_INT);
                                        $stmt->execute();
                                        $user_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    }

                                    // Update post count dynamically based on items count
                                    $user_posts_amount = count($user_items); 
                                    echo $user_posts_amount; ?>
                                </p>
                                <p class="m-0 fs-6">Posts</p>
                            </a>
                            <a id="user-profile-followers-amount"
                                class='d-flex user-profile-followers-amount-container align-items-center gap-1 text-decoration-none text-body fw-semibold'>
                                <p class="user-profile-followers-amount m-0 fs-6">
                                    <?php echo $user_followers_amount; ?>
                                </p>
                                <p class="m-0 fs-6">
                                    <?php echo $user_followers_amount === 1 ? 'Follower' : 'Followers'; ?>
                                </p>
                            </a>
                            <a id="user-profile-following-amount"
                                class='d-flex user-profile-following-amount-container align-items-center gap-1 text-decoration-none text-body fw-semibold'>
                                <p class="user-profile-following-amount m-0 fs-6">
                                    <?php echo $user_following_amount; ?>
                                </p>
                                <p class="m-0 fs-6">Following</p>
                            </a>
                        </div>
                        <div class="user-profile-bio-container">
                            <p class="fw-semibold m-0 fs-6">Bio</p>
                            <p class="user-profile-bio m-0">
                                <?php echo $user_bio; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="d-flex feed-container flex-column align-items-start align-items-center justify-content-center">
                    <div class="feed-top w-100 mb-4">
                        <h4 id="user-profile-posts" class="fw-semibold">Listed Items</h4>
                    </div>
                    <div class="feed-posts-container p-0 d-flex flex-column align-items-center justify-content-center w-100 gap-4">
                        <?php if (!empty($user_items)): ?>
                            <div class="items-grid row row-cols-1 row-cols-md-3 g-4">
                                <?php foreach ($user_items as $item): ?>
                                    <div class="col">
                                        <div class="card h-100">
                                            <img src="<?php echo $item['image_path']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo $item['name']; ?></h5>
                                                <p class="card-text"><?php echo $item['description']; ?></p>
                                                <p class="card-text fw-bold">$<?php echo number_format($item['price'], 2); ?></p>
                                                <?php if ($is_logged_in_user_profile): ?>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editItemModal<?php echo $item['id']; ?>">
                                                            Edit
                                                        </button>
                                                        <form action="execute_file.php?filename=delete_item.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit Modal for Each Item -->
                                    <?php if ($is_logged_in_user_profile): ?>
                                        <div class="modal fade" id="editItemModal<?php echo $item['id']; ?>" tabindex="-1" aria-labelledby="editItemModalLabel<?php echo $item['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editItemModalLabel<?php echo $item['id']; ?>">Edit Item</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="execute_file.php?filename=update_item.php" method="POST">
                                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="item_description<?php echo $item['id']; ?>" class="form-label">Description</label>
                                                                <textarea class="form-control" id="item_description<?php echo $item['id']; ?>" name="item_description" rows="3" required><?php echo $item['description']; ?></textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="item_price<?php echo $item['id']; ?>" class="form-label">Price</label>
                                                                <input type="number" step="0.01" class="form-control" id="item_price<?php echo $item['id']; ?>" name="item_price" value="<?php echo $item['price']; ?>" required>
                                                            </div>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center d-flex flex-column align-items-center justify-content-center gap-1">
                                <i class="text-secondary bi bi-emoji-frown h1"></i>
                                <p class="text-secondary fs-5">No items listed yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>