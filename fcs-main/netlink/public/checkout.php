<?php
session_start();
require_once('../private/utility_functions.php');
redirect_if_not_logged_in();
require_once('../private/config.php'); 

// Redirect to cart if accessed directly without cart items
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

require_once("stripe-php-16.6.0/stripe-php-16.6.0/init.php");
\Stripe\Stripe::setApiKey('sk_test_51R3K7RFZsqT1etgMMfcQsnhkQBqvASDsEqgsAPIrWd8bohJnSZ5g6YaEf7452Cx5z5peYJZAntfpUVsTWJntzBgE001jkyZmaA');

$cart_items = $_SESSION['cart'];
$total_amount = array_sum(array_map(function($item) {
    return $item['price'] * $item['quantity'];
}, $cart_items));

$error_message = '';
$payment_successful = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stripeToken'])) {
    $token = $_POST['stripeToken'];
    $amount = $total_amount * 100; // Convert to cents

    try {
        // Create Stripe charge
        $charge = \Stripe\Charge::create([
            'amount' => $amount,
            'currency' => 'usd',
            'description' => 'NetLink Order',
            'source' => $token,
        ]);

        // Payment success: clear cart and set success flag
        $_SESSION['cart'] = [];
        $payment_successful = true;

    } catch (\Stripe\Exception\CardException $e) {
        $error_message = $e->getError()->message;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checkout - NetLink</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Your provided CSS styles here */
        #checkout { max-width: 480px; margin: 0 auto; padding: 30px 0; }
        section { margin-bottom: 20px; }
        h2 { margin-bottom: 15px; font-size: 18px; font-weight: 500; color: #32325d; }
        fieldset { border: none; margin: 0; padding: 0; }
        label { display: block; margin-bottom: 10px; font-size: 14px; color: #8898aa; }
        input, select { width: 100%; padding: 10px; border: 1px solid #e8e8fb; border-radius: 4px; font-size: 14px; color: #32325d; }
        .payment-button { width: 100%; padding: 10px; background: #666ee8; color: #fff; border-radius:4px;border:none;font-size:16px;}
        .element-errors { color:#e25950;font-size:14px;margin-top:10px;}
        #name_tag { position: relative; top:50px;margin-bottom:2em;}
    </style>
</head>
<body class="h-100 w-100 m-0 p-0 preload">
<div class="w-100 h-100 body-container container-fluid m-0 p-0">
    <?php include('header.php'); ?>
    <?php include('sidebar.php'); ?>
    <main class="page-checkout d-flex flex-column h-100 bg-light align-items-center justify-content-start">
        <div class="checkout-container w-75 pt-5 pb-5">
            <div class="checkout-header w-100 mb-4">
                <p id="name_tag" class="h3 fw-semibold">Checkout</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($payment_successful): ?>
                <!-- Alert and redirect after successful payment -->
                <script>
                    alert('Your order is placed successfully!');
                    window.location.href='index.php';
                </script>
            <?php elseif (!empty($cart_items)): ?>
                <!-- Checkout form -->
                <div id="checkout">
                    <form id="payment-form" method="POST">
                        <!-- Shipping & Billing Information -->
                        <section>
                            <h2>Shipping & Billing Information</h2>
                            <fieldset>
                                <label><span>Name</span><input name="name" required></label>
                                <label><span>Email</span><input name="email" type="email" required></label>
                                <label><span>Address</span><input name="address" required></label>
                                <label><span>City</span><input name="city" required></label>
                                <label><span>State</span><input name="state" required></label>
                                <label><span>ZIP</span><input name="postal_code" required></label>
                                <label><span>Country</span>
                                    <select name="country" required>
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                        <option value="IN">India</option>
                                    </select>
                                </label>
                            </fieldset>
                        </section>

                        <!-- Payment Information -->
                        <section>
                            <h2>Payment Information (Total: $<?php echo number_format($total_amount,2); ?>)</h2>
                            <div id="card-element"></div>
                            <div id="card-errors" class="element-errors"></div>
                        </section>

                        <!-- Pay Button -->
                        <button class="payment-button">Pay</button>
                    </form>
                </div>

                <!-- Stripe JS -->
                <script src="https://js.stripe.com/v3/"></script>
                <script>
                    var stripe=Stripe('pk_test_51R3K7RFZsqT1etgMVjVSdw66mYs6h6MtYBcdONGXenpcVLpBCwWN0Ran1xpO1vcIpjntZwn8QAeJH7c27ImhNyaQ00luGnZcky');
                    var elements=stripe.elements();
                    var card=elements.create('card');
                    card.mount('#card-element');

                    var form=document.getElementById('payment-form');
                    form.addEventListener('submit',function(event){
                        event.preventDefault();
                        stripe.createToken(card).then(function(result){
                            if(result.error){
                                document.getElementById('card-errors').textContent=result.error.message;
                            }else{
                                var hiddenInput=document.createElement('input');
                                hiddenInput.setAttribute('type','hidden');
                                hiddenInput.setAttribute('name','stripeToken');
                                hiddenInput.setAttribute('value',result.token.id);
                                form.appendChild(hiddenInput);
                                form.submit();
                            }
                        });
                    });
                </script>

            <?php else: ?>
                <!-- Empty cart message -->
                <p>Your cart is empty.</p>
            <?php endif; ?>

        </div>
    </main>
</div>

</body>
</html>
