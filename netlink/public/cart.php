<?php
session_start();

require_once('../private/utility_functions.php');
redirect_if_not_logged_in();

require_once('../private/config.php'); 

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
// Handle removing items from the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $item_id = intval($_POST['item_id']);
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function($item) use ($item_id) {
        return $item['id'] !== $item_id;
    }));
}

// Handle updating item quantities (Corrected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $item_id = intval($_POST['item_id']);
    $new_quantity = intval($_POST['quantity']);

    // Create a new cart array to avoid modifying the array during iteration
    $updated_cart = [];

    foreach ($_SESSION['cart'] as $item) {
        if ($item['id'] === $item_id) {
            if ($new_quantity > 0) {
                // Update quantity if greater than zero
                $item['quantity'] = $new_quantity;
                $updated_cart[] = $item;
            }
            // If quantity is zero or negative, do not add the item (effectively removing it)
        } else {
            // Keep other items unchanged
            $updated_cart[] = $item;
        }
    }

    $_SESSION['cart'] = $updated_cart;
}


// Handle clearing the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (!empty($_SESSION['cart'])) {
        // Clear the cart and redirect to checkout.php // Clear the cart
        header("Location: checkout.php"); // Redirect to checkout.php
        exit();
    } else {
        echo "Cart is empty. Please add items before checking out.";
    }
}

// Fetch item details from the database for items in the cart
$cart_items = [];
$total_amount = 0;

if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $item_ids = array_column($_SESSION['cart'], 'id');
    if (!empty($item_ids)) {
        $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
        $stmt = $conn->prepare("SELECT * FROM items_table WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($item_ids)), ...$item_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $cart_item_index = array_search($row['id'], array_column($_SESSION['cart'], 'id'));
            if ($cart_item_index !== false) {
                $cart_item = $_SESSION['cart'][$cart_item_index];
                $cart_items[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'image_path' => $row['image_path'],
                    'quantity' => $cart_item['quantity']
                ];
                $total_amount += $row['price'] * $cart_item['quantity'];
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart - NetLink</title>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-item {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .details {
            flex: 1;
        }
        .title-price-x {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .title-price-x h4 {
            margin: 0;
        }
        .title-price-x i {
            cursor: pointer;
            color: #dc3545;
        }
        .buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .buttons i {
            cursor: pointer;
            color: #0d6efd;
        }
        .quantity {
            font-weight: bold;
        }
        .total-bill {
            margin-top: 20px;
            text-align: right;
        }
        .total-bill h2 {
            margin-bottom: 20px;
        }
        .checkout, .removeAll {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .checkout {
            background-color: #28a745;
            color: white;
        }
        .removeAll {
            background-color: #dc3545;
            color: white;
            margin-left: 10px;
        }
        .empty-cart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 50vh;
            text-align: center;
        }
        .empty-cart h2 {
            margin-bottom: 20px;
        }
        .empty-cart .HomeBtn {
            padding: 10px 20px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        #name_tag {
            position: relative;
            top: 50px;
            margin-bottom : 2em;
        }
    </style>
</head>
<body class="h-100 w-100 m-0 p-0 preload">
    <div class="w-100 h-100 body-container container-fluid m-0 p-0">
        <?php include('header.php'); ?>
        <?php include('sidebar.php'); ?>
        <main class="page-cart d-flex flex-column h-100 bg-light align-items-center justify-content-start">
            <div class="cart-container w-75 pt-5 pb-5">
                <div class="cart-header w-100 mb-4">
                    <p id="name_tag" class="h3 fw-semibold">Shopping Cart</p>
                </div>

                <div id="shopping-cart">
                    <?php if (!empty($cart_items)): ?>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <img src="<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="details">
                                    <div class="title-price-x">
                                        <h4 class="title-price">
                                            <p><?php echo htmlspecialchars($item['name']); ?></p>
                                            <p class="cart-item-price">Rs <?php echo number_format($item['price'], 2); ?></p>
                                        </h4>
                                        <form action="" method="POST" style="display: inline;">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="remove_item" class="btn btn-danger">Remove</button>
                                        </form>
                                    </div>
                                    <div class="buttons">
                                        <form action="" method="POST" style="display: inline;">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="form-control" style="width: 60px;">
                                            <button type="submit" name="update_quantity" class="btn btn-primary">Update</button>
                                        </form>
                                    </div>
                                    <h3>Rs <?php echo number_format($item['price'] * $item['quantity'], 2); ?></h3>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-cart">
                            <h2>Cart is Empty</h2>
                            <a href="index.php">
                                <button class="HomeBtn">Back to Home</button>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($cart_items)): ?>
                    <div class="total-bill">
                        <h2>Total Bill: Rs <?php echo number_format($total_amount, 2); ?></h2>
                        <form action="" method="POST">
                            <button type="submit" name="clear_cart" class="btn btn-danger">Clear Cart</button>
                            <button type="submit" name="checkout" class="btn btn-success">Checkout</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
