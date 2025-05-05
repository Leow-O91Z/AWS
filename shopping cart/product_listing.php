<?php include 'head.php'; ?>

<div class="container">
    <h1>Product Listing</h1>
    <div class="row">
        <?php
        // Example product data (replace with database query in real implementation)
        $products = [
            ['name' => 'Product 1', 'price' => '$10', 'image' => 'product1.jpg'],
            ['name' => 'Product 2', 'price' => '$20', 'image' => 'product2.jpg'],
            ['name' => 'Product 3', 'price' => '$30', 'image' => 'product3.jpg'],
        ];

        foreach ($products as $product) {
            echo '<div class="col-md-4">';
            echo '<div class="card">';
            echo '<img src="' . $product['image'] . '" class="card-img-top" alt="' . $product['name'] . '">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . $product['name'] . '</h5>';
            echo '<p class="card-text">Price: ' . $product['price'] . '</p>';
            echo '<a href="#" class="btn btn-primary">Add to Cart</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<?php include 'foot.php'; ?>
