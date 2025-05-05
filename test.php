<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output some basic information
echo "<h1>PHP Test Page</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test database connection
try {
    require_once 'database.php';
    echo "<p>Database connection successful!</p>";
    
    // Test query
    $stmt = $db->query("SELECT COUNT(*) FROM customer");
    $count = $stmt->fetchColumn();
    echo "<p>Number of customers in database: $count</p>";
    
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

// Show session info
echo "<h2>Session Information</h2>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

// Show server information
echo "<h2>Server Information</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
?>