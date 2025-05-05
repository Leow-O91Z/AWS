<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'user_actions';
$username = 'root';
$password = '';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Connection & Schema Check</title>
    
</head>
<body>
    <h1>Database Connection & Schema Check</h1>";

echo "<h2>Step 1: Checking MySQL server connection</h2>";
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>Successfully connected to MySQL server (<code>$host</code>)</div>";
    echo "<h2>Step 2: Checking if database '<code>$dbname</code>' exists</h2>";
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    $databaseExists = $stmt->fetchColumn();

    if ($databaseExists) {
        echo "<div class='success'>Database '<code>$dbname</code>' exists.</div>";
        echo "<h2>Step 3: Connecting to database '<code>$dbname</code>'</h2>";
        try {
            $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div class='success'>Successfully connected to database '<code>$dbname</code>'.</div>";
            echo "<h2>Step 4: Checking required database tables</h2>";
            $expected_tables = ['admin', 'brand', 'cart', 'category', 'customer', 'inventory', 'payment', 'porder', 'product', 'shipping'];
            $foundTables = [];
            $missingTables = [];

            $stmtTables = $db->query("SHOW TABLES");
            $actualTables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);

            foreach ($expected_tables as $table) {
                if (in_array($table, $actualTables)) {
                    echo "<div class='info'>Table '<code>$table</code>' exists.</div>";
                    $foundTables[] = $table;
                } else {
                    echo "<div class='error'>Table '<code>$table</code>' is MISSING.</div>";
                    $missingTables[] = $table;
                }
            }

            $extraTables = array_diff($actualTables, $expected_tables);
            if (!empty($extraTables)) {
                 echo "<div class='warning'>Warning: Found extra tables not defined in the core <code>user_actions.sql</code>: <code>" . implode('</code>, <code>', $extraTables) . "</code>. These might be from previous versions or custom additions.</div>";
            }


            if (!empty($missingTables)) {
                echo "<div class='error'><strong>Action Required:</strong> Some required tables are missing. You need to import the structure from your <code>user_actions.sql</code> file.</div>";
                echo "<p>Use a tool like phpMyAdmin or the MySQL command line to import <code>user_actions.sql</code> into the '<code>$dbname</code>' database.</p>";
            } else {
                echo "<div class='success'>All required tables from <code>user_actions.sql</code> seem to exist!</div>";
                echo "<h2>Step 5: Password Column Check (Manual Verification Recommended)</h2>";
                echo "<div class='warning'><strong>Important Security Check:</strong> Please manually verify in your database (e.g., using phpMyAdmin) that the '<code>password</code>' columns in the '<code>customer</code>' and '<code>admin</code>' tables are of type <strong><code>VARCHAR(255)</code></strong>. If they are <code>VARCHAR(20)</code> as originally defined in <code>user_actions.sql</code>, passwords will be stored insecurely. <br>Run: <pre>ALTER TABLE customer MODIFY COLUMN password VARCHAR(255) NOT NULL;\nALTER TABLE admin MODIFY COLUMN password VARCHAR(255) NOT NULL;</pre> if needed.</div>";
                echo "<p style='margin-top: 20px;'><a href='index.php' class='btn'>Go to Homepage</a></p>";
            }

        } catch (PDOException $e) {
            echo "<div class='error'>Failed to connect to database '<code>$dbname</code>': " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<p>Possible solutions:</p>
                <ul>
                    <li>Check that the database name '<code>$dbname</code>' is correct in <code>database.php</code>.</li>
                    <li>Ensure the MySQL user '<code>$username</code>' has privileges to access this database.</li>
                </ul>";
        }
    } else {
        echo "<div class='error'>Database '<code>$dbname</code>' does not exist.</div>";
        echo "<p><strong>Action Required:</strong></p>
              <ol>
                <li>Create the database '<code>$dbname</code>' on your MySQL server.</li>
                <li>Import the table structure from your <code>user_actions.sql</code> file into the newly created database using phpMyAdmin or the command line.</li>
                <li><strong>CRITICAL:</strong> After importing, run the SQL command to fix password security: <br><pre>ALTER TABLE customer MODIFY COLUMN password VARCHAR(255) NOT NULL;\nALTER TABLE admin MODIFY COLUMN password VARCHAR(255) NOT NULL;</pre></li>
              </ol>";
    }

} catch (PDOException $e) {
    echo "<div class='error'>Failed to connect to MySQL server: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Possible solutions:</p>
        <ul>
            <li>Make sure your MySQL server (e.g., XAMPP, WAMP, MAMP) is running.</li>
            <li>Check that the hostname '<code>$host</code>' is correct in <code>database.php</code>.</li>
            <li>Verify the MySQL username ('<code>$username</code>') and password in <code>database.php</code>.</li>
            <li>Ensure your MySQL server is configured to accept connections.</li>
        </ul>";
}

echo "</body></html>";
