<?php
$host = 'localhost';
$dbname = 'user_actions';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO("mysql:host=$host;charset=$charset", $username, $password, $options);
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    $databaseExists = $stmt->fetchColumn();

    if (!$databaseExists) {
        $pdo->exec("CREATE DATABASE `$dbname` DEFAULT CHARACTER SET $charset COLLATE {$charset}_unicode_ci");
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
            echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;'>
                    Database '$dbname' created successfully! You may need to import your user_actions.sql structure now.
                  </div>";
        }
    }

    $pdo->exec("USE `$dbname`");
    $db = $pdo;

} catch (PDOException $e) {
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $db = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e2) {
        error_log('Database Connection Error: ' . $e2->getMessage());

        $mysqlRunning = false;
        try {
            $testConnection = new PDO("mysql:host=$host", $username, $password);
            $mysqlRunning = true;
        } catch (PDOException $e3) {
            $mysqlRunning = false;
        }

        if ($mysqlRunning) {
            die('Database connection failed. The database "' . htmlspecialchars($dbname) . '" might not exist or the schema is incorrect. Please ensure the database exists and the structure matches user_actions.sql.');
        } else {
            die('Database connection failed. Please make sure your MySQL server is running.');
        }
    }
}
?>

