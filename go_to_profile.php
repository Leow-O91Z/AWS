<?php
session_start();

require_once 'database.php';
require_once 'User.php';

if (!isset($_SESSION['customerID'])) {
    
    try {
        $stmt = $db->prepare("SELECT * FROM customer WHERE email = ?");
        $stmt->execute(['test@example.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            try {
                $customerID = 'CUST-' . rand(10000, 99999);
                
                $stmt = $db->prepare("INSERT INTO customer (customerID, name, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$customerID, 'Test User', 'test@example.com', password_hash('password123', PASSWORD_DEFAULT)]);
                
                $stmt = $db->prepare("INSERT INTO customer_profile (customerID) VALUES (?)");
                $stmt->execute([$customerID]);
                
                $_SESSION['customerID'] = $customerID;
                $_SESSION['name'] = 'Test User';
                $_SESSION['email'] = 'test@example.com';
            } catch (PDOException $e) {
                User::ensureTablesExist();

                $customerID = 'CUST-' . rand(10000, 99999);
                $stmt = $db->prepare("INSERT INTO customer (customerID, name, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$customerID, 'Test User', 'test@example.com', password_hash('password123', PASSWORD_DEFAULT)]);
                
                $stmt = $db->prepare("INSERT INTO customer_profile (customerID) VALUES (?)");
                $stmt->execute([$customerID]);
                
                $_SESSION['customerID'] = $customerID;
                $_SESSION['name'] = 'Test User';
                $_SESSION['email'] = 'test@example.com';
            }
        } else {
            $_SESSION['customerID'] = $user['customerID'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
        }
    } catch (PDOException $e) {
        User::ensureTablesExist();
        
        header('Location: go_to_profile.php');
        exit;
    }
}

header('Location: index.php?page=profile');
exit;
?>