<?php

$products = [
    ['id' => 1, 'name' => 'Product A', 'price' => 10.00],
    ['id' => 2, 'name' => 'Product B', 'price' => 20.00],
    ['id' => 3, 'name' => 'Product C', 'price' => 30.00],
];

// Display product list
echo "<h1>Product Listing</h1>";
foreach ($products as $product) {
    echo "<div class='product-box'>";
    echo "<h3>{$product['name']}</h3>";
    echo "<p>Price: $ {$product['price']}</p>";
    echo "<a href='product-detail.php?id={$product['id']}'><button>View Details</button></a>";
    echo "<a href='shopping-cart.php?add={$product['id']}'><button>Add to Cart</button></a>";
    echo "</div>";
}

echo "<br><a href='homepage.php'><button>Back to Homepage</button></a>";
echo "<br><a href='shopping-cart.php'><button>View Cart</button></a>";
?>