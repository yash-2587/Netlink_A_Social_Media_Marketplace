<?php
session_start();

require_once('../private/utility_functions.php');
redirect_if_not_logged_in();

require_once('../private/config.php'); 

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding items to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = intval($_POST['item_id']);
    $item_name = htmlspecialchars($_POST['item_name']);
    $item_price = floatval($_POST['item_price']);

    // Check if item already in cart
    $already_in_cart = array_search($item_id, array_column($_SESSION['cart'], 'id')) !== false;

    if (!$already_in_cart) {
        $_SESSION['cart'][] = [
            'id' => $item_id,
            'name' => $item_name,
            'price' => $item_price,
            'quantity' => 1
        ];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_item'])) {
    $item_name = htmlspecialchars($_POST['item_name']);
    $item_description = htmlspecialchars($_POST['item_description']);
    $item_price = floatval($_POST['item_price']);
    $user_id = $_SESSION['user_id']; 

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/items/'; 
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); 
        }

        $file_name = basename($_FILES['item_image']['name']);
        $file_path = $upload_dir . uniqid() . '_' . $file_name; 

        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $file_path)) {

            $stmt = $conn->prepare("INSERT INTO items_table (user_id, name, description, price, image_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issds", $user_id, $item_name, $item_description, $item_price, $file_path);

            if ($stmt->execute()) {
                $success_message = "Item listed successfully!";
            } else {
                $error_message = "Failed to list item. Please try again.";
            }
            $stmt->close();
        } else {
            $error_message = "Failed to upload image. Please try again.";
        }
    } else {
        $error_message = "Please upload an image for the item.";
    }
}


// Fetch all items from database
$items = [];
$query = "SELECT * FROM items_table";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Get IDs of items currently in cart
$cart_item_ids = array_column($_SESSION['cart'], 'id');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Marketplace - NetLink</title>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="h-100 w-100 m-0 p-0 preload">
<div class="w-100 h-100 body-container container-fluid m-0 p-0">
    <?php include('header.php'); ?>
    <?php include('sidebar.php'); ?>

    <main class="page-marketplace d-flex flex-column h-100 bg-light align-items-center justify-content-start">
        <div class="marketplace-container w-75 pt-5 pb-5">
            <div class="marketplace-header w-100 mb-4">
                <p class="h3 fw-semibold">Marketplace</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="bi bi-plus-lg"></i> List an Item
                </button>
            </div>

            <div class="items-grid row row-cols-1 row-cols-md-3 g-4">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <?php 
                            // Check if current item is already in cart
                            $is_in_cart = in_array($item['id'], $cart_item_ids);
                        ?>
                        <div class="col">
                            <div class="card h-100">
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <p class="card-text fw-bold">$<?php echo number_format($item['price'], 2); ?></p>

                                    <!-- Only Add to Cart button -->
                                    <form action="" method="POST">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($item['name']); ?>">
                                        <input type="hidden" name="item_price" value="<?php echo htmlspecialchars($item['price']); ?>">

                                        <?php if ($is_in_cart): ?>
                                            <!-- Disabled button if already added -->
                                            <button type="button" class="btn btn-secondary w-100 mb-2" disabled>Already Added</button>
                                        <?php else: ?>
                                            <!-- Active Add to Cart Button -->
                                            <button type="submit" name="add_to_cart" class="btn btn-primary w-100 mb-2">Add to Cart</button>
                                        <?php endif; ?>
                                    </form>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No items available in the marketplace.</p>
                <?php endif; ?>
            </div>

        </div>
    </main>

</div>

<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="addItemModalLabel">List an Item</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <form action="" method="POST" enctype="multipart/form-data">
                         <div class="mb-3">
                             <label for="item_name" class="form-label">Item Name</label>
                             <input type="text" class="form-control" id="item_name" name="item_name" required>
                         </div>
                         <div class="mb-3">
                             <label for="item_description" class="form-label">Description</label>
                             <textarea class="form-control" id="item_description" name="item_description" rows="3" required></textarea>
                         </div>
                         <div class="mb-3">
                             <label for="item_price" class="form-label">Price</label>
                             <input type="number" step="0.01" class="form-control" id="item_price" name="item_price" required>
                         </div>
                         <div class="mb-3">
                             <label for="item_image" class="form-label">Item Image</label>
                             <input type="file" class="form-control" id="item_image" name="item_image" accept="image/*" required>
                         </div>
                         <button type="submit" name="submit_item" class="btn btn-primary">Submit</button>
                     </form>
                 </div>
             </div>
         </div>
</div>

</body>
</html>
