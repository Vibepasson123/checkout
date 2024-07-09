<?php
require_once 'Checkout.php';
session_start();

// Default pricing rules
$defaultPricingRules = [
    'A' => ['price' => 50],
    'B' => ['price' => 75, 'special' => ['type' => 'multipriced', 'quantity' => 2, 'price' => 125]],
    'C' => ['price' => 25, 'special' => ['type' => 'buy_n_get_1_free', 'quantity' => 3]],
    'D' => ['price' => 150, 'special' => ['type' => 'meal_deal', 'pair' => 'E', 'price' => 300]],
    'E' => ['price' => 200, 'special' => ['type' => 'meal_deal', 'pair' => 'D', 'price' => 300]],
];

// Initialize or retrieve the checkout instance
if (!isset($_SESSION['checkout'])) {
    $_SESSION['checkout'] = new Checkout($defaultPricingRules);
}
$checkout = $_SESSION['checkout'];

// Retrieve or initialize the pricing rules
if (!isset($_SESSION['pricingRules'])) {
    $_SESSION['pricingRules'] = $defaultPricingRules;
}
$pricingRules = $_SESSION['pricingRules'];

// Function to update prices
function updatePrices($newPrices)
{
    global $pricingRules;
    foreach ($newPrices as $item => $price) {
        if (isset($pricingRules[$item]) && is_numeric($price)) {
            $pricingRules[$item]['price'] = intval($price);
        }
    }
    $_SESSION['pricingRules'] = $pricingRules;
}

// Function to update special rules
function updateSpecialRules($newSpecialRules)
{
    global $pricingRules;
    foreach ($newSpecialRules as $item => $special) {
        if (isset($pricingRules[$item]) && is_array($special)) {
            $pricingRules[$item]['special'] = $special;
        }
    }
    $_SESSION['pricingRules'] = $pricingRules;
}

// Handle form submissions
$validItems = ['A', 'B', 'C', 'D', 'E'];
$message = '';
$warning = '';

$multipricedItem = $multipricedQuantity = $multipricedPrice = '';
$buyNGet1FreeItem = $buyNGet1FreeQuantity = '';
$mealDealItem1 = $mealDealItem2 = $mealDealPrice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set-pricing'])) {
        // Update pricing rules
        $newPrices = [];
        $errors = [];
        foreach ($validItems as $item) {
            $priceKey = 'price-' . $item;
            if (empty($_POST[$priceKey])) {
                $errors[] = "Price for item $item is required.";
            } else {
                $newPrices[$item] = $_POST[$priceKey];
            }
        }

        if (empty($errors)) {
            updatePrices($newPrices);
            $_SESSION['checkout'] = new Checkout($_SESSION['pricingRules'], $defaultPricingRules);
            $checkout = $_SESSION['checkout'];
            $message = "Pricing rules set successfully.";
        } else {
            $message = implode('<br>', $errors);
        }
    } elseif (isset($_POST['set-special-pricing'])) {
        // Add special pricing rules
        $newSpecialRules = [];

        if (!empty($_POST['multipriced-item']) && !empty($_POST['multipriced-quantity']) && !empty($_POST['multipriced-price'])) {
            $multipricedItem = $_POST['multipriced-item'];
            $multipricedQuantity = intval($_POST['multipriced-quantity']);
            $multipricedPrice = intval($_POST['multipriced-price']);
            $newSpecialRules[$multipricedItem] = [
                'type' => 'multipriced',
                'quantity' => $multipricedQuantity,
                'price' => $multipricedPrice
            ];
        }

        if (!empty($_POST['buy_n_get_1_free-item']) && !empty($_POST['buy_n_get_1_free-quantity'])) {
            $buyNGet1FreeItem = $_POST['buy_n_get_1_free-item'];
            $buyNGet1FreeQuantity = intval($_POST['buy_n_get_1_free-quantity']);
            $newSpecialRules[$buyNGet1FreeItem] = [
                'type' => 'buy_n_get_1_free',
                'quantity' => $buyNGet1FreeQuantity
            ];
        }

        if (!empty($_POST['meal_deal-item1']) && !empty($_POST['meal_deal-item2']) && !empty($_POST['meal_deal-price'])) {
            $mealDealItem1 = $_POST['meal_deal-item1'];
            $mealDealItem2 = $_POST['meal_deal-item2'];
            $mealDealPrice = intval($_POST['meal_deal-price']);
            $newSpecialRules[$mealDealItem1] = [
                'type' => 'meal_deal',
                'pair' => $mealDealItem2,
                'price' => $mealDealPrice
            ];
            $newSpecialRules[$mealDealItem2] = [
                'type' => 'meal_deal',
                'pair' => $mealDealItem1,
                'price' => $mealDealPrice
            ];
        }

        updateSpecialRules($newSpecialRules);
        $_SESSION['checkout'] = new Checkout($_SESSION['pricingRules'], $defaultPricingRules);
        $checkout = $_SESSION['checkout'];
        $message = "Special pricing rules set successfully.";
    } elseif (isset($_POST['scan-item'])) {
        // Scan item
        $item = strtoupper($_POST['item']);
        if (empty($item)) {
            $message = "Item is required.";
        } elseif (!in_array($item, $validItems)) {
            $message = "Invalid item '$item'. Only items A, B, C, D, and E are allowed.";
        } elseif (!isset($pricingRules[$item]['price'])) {
            $message = "Price for item '$item' is not set. Please set the price first.";
        } else {
            $checkout->scan($item);
            $message = "Item '$item' scanned successfully.";
        }
    } elseif (isset($_POST['clear-session'])) {
        // Clear session
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['checkout'] = new Checkout($defaultPricingRules);
        $_SESSION['pricingRules'] = $defaultPricingRules;
        $checkout = $_SESSION['checkout'];
        $message = "Session cleared. Ready for new checkout.";
    }
}

$totalPrice = $checkout->total();
$scannedItems = $checkout->getItems();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supermarket Checkout</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <h1>Supermarket Checkout</h1>
    <?php include 'form.html'; ?>
</body>

</html>