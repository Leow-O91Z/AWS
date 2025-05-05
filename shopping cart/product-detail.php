<?php

$products = [
    1 => ['name' => 'Product A', 'price' => 10.00, 'description' => 'Description of Product A'],
    2 => ['name' => 'Product B', 'price' => 20.00, 'description' => 'Description of Product B'],
    3 => ['name' => 'Product C', 'price' => 30.00, 'description' => 'Description of Product C'],
];

// Get product ID from query string
$id = $_GET['id'] ?? null;

if ($id && isset($products[$id])) {
    $product = $products[$id];
    echo "<div class='product-box'>";
    echo "<h1>{$product['name']}</h1>";
    echo "<p>Price: $ {$product['price']}</p>";
    echo "<p>{$product['description']}</p>";
    echo "<a href='shopping-cart.php?add={$id}'><button>Add to Cart</button></a>";
    echo "<a href='shopping-cart.php'><button>View Cart</button></a>";
    echo "</div>";
    echo "<br><a href='product-listing.php'><button>Back to Product Listing</button></a>";
} else {
    echo "<p>Product not found.</p>";
}
?>