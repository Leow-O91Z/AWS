<?php
/**
 * PASSWORD RESET SYSTEM
 * 
 * FUNCTIONS:
 * 1. Authentication - Verifies user is logged in
 * 2. Password Validation - Validates password complexity requirements
 * 3. Password Update - Securely updates user password
 * 4. Security - Implements secure password handling
 */

session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: customer_profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($currentPassword)) {
        $errors[] = 'Current password is required.';
    }
    
    if (empty($newPassword)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $errors[] = 'New password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $newPassword)) {
        $errors[] = 'New password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $newPassword)) {
        $errors[] = 'New password must contain at least one number.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
        $errors[] = 'New password must contain at least one special character.';
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $errors[] = 'User not found.';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                
                header('Location: customer_profile.php?password_reset=1');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['password_reset_errors'] = $errors;
        header('Location: customer_profile.php');
        exit;
    }
} else {
    header('Location: customer_profile.php');
    exit;
}
?>