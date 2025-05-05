<?php

$products = [
    ['id' => 1, 'name' => 'Product A', 'price' => 10.00],
    ['id' => 2, 'name' => 'Product B', 'price' => 20.00],
    ['id' => 3, 'name' => 'Product C', 'price' => 30.00],
];

// Get search query
$query = $_GET['q'] ?? '';

echo "<h1>Search Results</h1>";
if ($query) {
    $results = array_filter($products, function ($product) use ($query) {
        return stripos($product['name'], $query) !== false;
    });

    if ($results) {
        foreach ($results as $product) {
            echo "<p><a href='product-detail.php?id={$product['id']}'>{$product['name']} - $ {$product['price']}</a> 
                  <a href='shopping-cart.php?add={$product['id']}'>Add to Cart</a></p>";
        }
    } else {
        echo "<p>No products found.</p>";
    }
} else {
    echo "<p>Please enter a search query.</p>";
}
?>