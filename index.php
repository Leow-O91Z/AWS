<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';

$page = $_GET['page'] ?? 'home';
$currentPagePHP = $page;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $password_input = $_POST['password'] ?? '';
        $login_error_message = 'Incorrect email or password.';

        if (empty($email) || empty($password_input)) {
            $_SESSION['login_error'] = $login_error_message;
            $_SESSION['active_form'] = 'login';
        } else {
            try {
                $stmt = $db->prepare("SELECT customerID, name, email, password FROM customer WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password_input, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['customerID'] = $user['customerID'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['isCustomer'] = true;
                    unset($_SESSION['isAdmin']); 

                    $customerID = $user['customerID'];
                    $sessionCart = $_SESSION['cart'] ?? []; 

                    $stmtCart = $db->prepare("SELECT productID, quantity FROM persistent_cart WHERE customerID = ?");
                    $stmtCart->execute([$customerID]);
                    $persistentItems = $stmtCart->fetchAll(PDO::FETCH_KEY_PAIR); 

                    foreach ($persistentItems as $productID => $quantity) {
                        $sessionCart[$productID] = ($sessionCart[$productID] ?? 0) + $quantity;
                    }

                    $_SESSION['cart'] = $sessionCart;

                    if (!empty($persistentItems)) {
                        $stmtClear = $db->prepare("DELETE FROM persistent_cart WHERE customerID = ?");
                        $stmtClear->execute([$customerID]);
                    }


                    $redirect_url = $_SESSION['redirect_after_login'] ?? 'index.php?page=home'; 
                    unset($_SESSION['redirect_after_login']);
                    header("Location: " . $redirect_url);
                    exit();
                } else {
                     $_SESSION['login_error'] = $login_error_message;
                     $_SESSION['active_form'] = 'login';
                }
            } catch (PDOException $e) {
                error_log("Login POST DB Error (incl. cart): " . $e->getMessage()); 
                $_SESSION['login_error'] = 'An error occurred during login. Please try again.';
                $_SESSION['active_form'] = 'login';
            }
        }
        header("Location: index.php?page=login_register");
        exit();
    }

    elseif (isset($_POST['register'])) {
        $email = trim($_POST['email'] ?? '');
        $password_input = $_POST['password'] ?? '';
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $confirm_password = $_POST['confirm_password'] ?? '';
        $name = trim($first_name . ' ' . $last_name);
        $errors = [];

        // Validation
        if (empty($name)) $errors[] = "First and Last Name are required";
        elseif (strlen($name) > 50) $errors[] = "Combined name cannot exceed 50 characters";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
        elseif (strlen($email) > 100) $errors[] = "Email cannot exceed 100 characters";
        else {
            if (empty($errors)) { 
                try {
                    $stmt = $db->prepare("SELECT email FROM customer WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->rowCount() > 0) $errors[] = 'Email is already registered!';
                } catch (PDOException $e) {
                     error_log("Reg POST DB Error (Email Check): " . $e->getMessage());
                     $errors[] = "Database error checking email.";
                }
            }
        }
        if (empty($password_input)) {
            $errors[] = "Password is required.";
        } else {
            if (strlen($password_input) < 8) $errors[] = "Password must be at least 8 characters long.";
            if (!preg_match('/[!@#$%^&*()_+=\-[\]{};\':"\\|,.<>\/?]/', $password_input)) $errors[] = "Password must contain at least one special character (e.g., !@#$%^&*).";
        }
        if ($password_input !== $confirm_password) $errors[] = "Passwords do not match";

        if (empty($errors)) {
            $customerID = null; $maxAttempts = 10; $attempt = 0;
            try {
                do {
                    $randomNumber = mt_rand(100000, 999999);
                    $potentialID = "CUS" . $randomNumber;
                    if (strlen($potentialID) > 30) throw new Exception("Generated customerID exceeds 30 characters.");
                    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM customer WHERE customerID = ?");
                    $stmtCheck->execute([$potentialID]);
                    if ($stmtCheck->fetchColumn() == 0) $customerID = $potentialID;
                    $attempt++;
                } while ($customerID === null && $attempt < $maxAttempts);
                if ($customerID === null) throw new Exception("Failed to generate unique customerID.");

                $password = password_hash($password_input, PASSWORD_DEFAULT);
                if ($password === false) throw new Exception("Password hashing failed.");

                $db->beginTransaction();
                $stmt_insert = $db->prepare("INSERT INTO customer (customerID, name, email, password) VALUES (?, ?, ?, ?)");
                $stmt_insert->execute([$customerID, $name, $email, $password]);
                $db->commit();

                $_SESSION['login_message'] = 'Registration successful! Please log in.';
                $_SESSION['active_form'] = 'login';
                header("Location: index.php?page=login_register&registration=success");
                exit();
            } catch (PDOException $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Reg POST DB Error (Insert): " . $e->getMessage());
                $_SESSION['register_error'] = 'Registration failed due to a database error.';
                $_SESSION['active_form'] = 'register';
            } catch (Exception $e) {
                 error_log("Reg POST Error: " . $e->getMessage());
                 $_SESSION['register_error'] = 'An unexpected error occurred during registration.';
                 $_SESSION['active_form'] = 'register';
            }
        } else {
            $_SESSION['register_error'] = implode("<br>", $errors);
            $_SESSION['active_form'] = 'register';
            $_SESSION['form_data'] = $_POST; 
        }
        header("Location: index.php?page=login_register");
        exit();
    }

    elseif (isset($_POST['admin_login'])) {
        $email = trim($_POST['email'] ?? '');
        $password_input = $_POST['password'] ?? '';
        $login_error_message = 'Incorrect admin email or password.'; 

        if (empty($email) || empty($password_input)) {
            $_SESSION['admin_login_error'] = 'Admin email and password are required.';
            header("Location: index.php?page=admin_login"); exit();
        }

        try {
            $stmt = $db->prepare("SELECT adminID, name, email, password FROM admin WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password_input, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['adminID'] = $admin['adminID'];
                $_SESSION['adminName'] = $admin['name'];
                $_SESSION['isAdmin'] = true;
                unset($_SESSION['customerID'], $_SESSION['name'], $_SESSION['email'], $_SESSION['isCustomer']);

                $redirect_url = $_SESSION['redirect_after_admin_login'] ?? 'index.php?page=admin_dashboard';
                unset($_SESSION['redirect_after_admin_login']);
                header("Location: " . $redirect_url);
                exit();
            } else {
                $_SESSION['admin_login_error'] = $login_error_message;
                header("Location: index.php?page=admin_login"); exit();
            }
        } catch (PDOException $e) {
            error_log("Admin Login DB Error: " . $e->getMessage());
            $_SESSION['admin_login_error'] = 'An unexpected error occurred during login.';
            header("Location: index.php?page=admin_login"); exit();
        }
    }

    elseif (isset($_POST['action']) && $_POST['action'] === 'add_product') {
        if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
            $_SESSION['admin_login_notice'] = 'Admin access required.';
            header('Location: index.php?page=admin_login'); exit;
        }

        $productID = trim($_POST['productID'] ?? '');
        $pname = trim($_POST['pname'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $stockquantity = filter_input(INPUT_POST, 'stockquantity', FILTER_VALIDATE_INT);
        $category = trim($_POST['category'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $sizeoption = trim($_POST['sizeoption'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $imageFile = $_FILES['product_image'] ?? null;
        $imagePath = null;

        $errors = [];
        if (empty($productID)) $errors[] = "Product ID is required.";
        elseif (strlen($productID) > 40) $errors[] = "Product ID cannot exceed 40 characters.";
        // Server-side format validation for productID
        elseif (!preg_match('/^[A-Z]+[0-9]{3}$/', $productID)) $errors[] = "Product ID format must be uppercase followed by 3 numbers (e.g., FLO175).";
        if (empty($pname)) $errors[] = "Product Name is required.";
        elseif (strlen($pname) > 100) $errors[] = "Product Name cannot exceed 100 characters.";
        if ($price === false || $price < 0) $errors[] = "Valid Price is required.";
        if ($stockquantity === false || $stockquantity < 0) $errors[] = "Valid Stock Quantity is required.";
        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'images/products/'; 
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                     $errors[] = "Failed to create image upload directory.";
                }
            }
            if (empty($errors)) {
                $fileName = $productID . '_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($imageFile['name']));
                $targetFile = $uploadDir . $fileName;
                $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

                $check = @getimagesize($imageFile['tmp_name']);
                if ($check === false) $errors[] = "File is not a valid image.";
                elseif ($imageFile['size'] > 2 * 1024 * 1024) $errors[] = "Image file is too large (Max 2MB).";
                elseif (!in_array($imageFileType, $allowedTypes)) $errors[] = "Only JPG, JPEG, PNG, GIF files are allowed.";
                else {
                    if (move_uploaded_file($imageFile['tmp_name'], $targetFile)) {
                        $imagePath = $targetFile; 
                    } else {
                        $errors[] = "Failed to upload product image.";
                    }
                }
            }
        } elseif ($imageFile && $imageFile['error'] !== UPLOAD_ERR_NO_FILE) {
             $errors[] = "Error uploading image file: Code " . $imageFile['error'];
        }


        if (empty($errors)) {
            try {
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM product WHERE productID = ?");
                $stmtCheck->execute([$productID]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $_SESSION['admin_product_error'] = "Product ID '$productID' already exists.";
                    if ($imagePath && file_exists($imagePath)) @unlink($imagePath); 
                } else {
                    $sql = "INSERT INTO product (productID, pname, brand, category, price, description, sizeoption, color, stockquantity, image_path)
                            VALUES (:productID, :pname, :brand, :category, :price, :description, :sizeoption, :color, :stockquantity, :image_path)";
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':productID', $productID);
                    $stmt->bindParam(':pname', $pname);
                    $stmt->bindParam(':brand', $brand);
                    $stmt->bindParam(':category', $category);
                    $stmt->bindParam(':price', $price);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':sizeoption', $sizeoption);
                    $stmt->bindParam(':color', $color);
                    $stmt->bindParam(':stockquantity', $stockquantity, PDO::PARAM_INT);
                    $stmt->bindParam(':image_path', $imagePath); 

                    if ($stmt->execute()) {
                        $_SESSION['admin_product_success'] = "Product '$pname' added successfully.";
                    } else {
                        $_SESSION['admin_product_error'] = "Failed to add product. Database error.";
                        if ($imagePath && file_exists($imagePath)) @unlink($imagePath); 
                    }
                }
            } catch (PDOException $e) {
                error_log("Admin Add Product DB Error: " . $e->getMessage());
                if ($e->getCode() == 23000) $_SESSION['admin_product_error'] = "Failed to add product. Product ID '$productID' might already exist.";
                else $_SESSION['admin_product_error'] = "An unexpected database error occurred.";
                if ($imagePath && file_exists($imagePath)) @unlink($imagePath); 
            }
        } else {
            $_SESSION['admin_product_error'] = "Failed to add product:<br>" . implode("<br>", $errors);
            $_SESSION['admin_product_form_data'] = $_POST; 
            header("Location: index.php?page=admin_manage_products&action=add"); exit();
        }
        header("Location: index.php?page=admin_manage_products"); exit();
    }

    elseif (isset($_POST['action']) && $_POST['action'] === 'update_product') {
        if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
            $_SESSION['admin_login_notice'] = 'Admin access required.';
            header('Location: index.php?page=admin_login'); exit;
        }

        // Get both original and potentially new product ID
        $originalProductID = trim($_POST['original_productID'] ?? ''); // Get the original ID
        $productID = trim($_POST['productID'] ?? '');
        $pname = trim($_POST['pname'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $stockquantity = filter_input(INPUT_POST, 'stockquantity', FILTER_VALIDATE_INT);
        $category = trim($_POST['category'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $sizeoption = trim($_POST['sizeoption'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $imageFile = $_FILES['product_image'] ?? null;
        $currentImagePath = $_POST['current_image_path'] ?? null;
        $newImagePath = null;

        $errors = [];
        if (empty($originalProductID)) $errors[] = "Original Product ID is missing. Cannot update."; // Need original ID
        if (empty($productID)) $errors[] = "Product ID cannot be empty.";
        // Server-side format validation for potentially new productID
        elseif (!preg_match('/^[A-Z]+[0-9]{3}$/', $productID)) $errors[] = "Product ID format must be uppercase followed by 3 numbers (e.g., FLO175).";
        if (empty($pname)) $errors[] = "Product Name is required.";
        if ($price === false || $price < 0) $errors[] = "Valid Price is required.";
        if ($stockquantity === false || $stockquantity < 0) $errors[] = "Valid Stock Quantity is required.";

        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'images/products/'; 
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                     $errors[] = "Failed to create image upload directory.";
                }
            }
            if (empty($errors)) {
                $fileName = $productID . '_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($imageFile['name']));
                $targetFile = $uploadDir . $fileName;
                $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

                $check = @getimagesize($imageFile['tmp_name']);
                if ($check === false) $errors[] = "File is not a valid image.";
                elseif ($imageFile['size'] > 2 * 1024 * 1024) $errors[] = "Image file is too large (Max 2MB).";
                elseif (!in_array($imageFileType, $allowedTypes)) $errors[] = "Only JPG, JPEG, PNG, GIF files are allowed.";
                else {
                    if (move_uploaded_file($imageFile['tmp_name'], $targetFile)) {
                        $newImagePath = $targetFile; 
                    } else {
                        $errors[] = "Failed to upload new product image.";
                    }
                }
            }
        } elseif ($imageFile && $imageFile['error'] !== UPLOAD_ERR_NO_FILE) {
             $errors[] = "Error uploading image file: Code " . $imageFile['error'];
        }

        if (empty($errors)) {
            try {
                // Check if the new productID already exists IF it's different from the original
                if ($productID !== $originalProductID) {
                    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM product WHERE productID = ?");
                    $stmtCheck->execute([$productID]);
                    if ($stmtCheck->fetchColumn() > 0) {
                        $errors[] = "The new Product ID '$productID' already exists. Choose a different ID.";
                    }
                }
            } catch (PDOException $e) {
                 error_log("Admin Update Product ID Check DB Error: " . $e->getMessage());
                 $errors[] = "Database error checking new Product ID.";
            }

            if (empty($errors)) { // Proceed only if ID check passed
                try {
                $sql = "UPDATE product SET
                            productID = :productID, -- Update the product ID itself
                            pname = :pname, brand = :brand, category = :category, price = :price,
                            description = :description, sizeoption = :sizeoption, color = :color,
                            stockquantity = :stockquantity";
                $params = [
                    ':productID' => $productID, // The new ID value
                    ':pname' => $pname, ':brand' => $brand, ':category' => $category, ':price' => $price,
                    ':description' => $description, ':sizeoption' => $sizeoption, ':color' => $color,
                    ':stockquantity' => $stockquantity, ':originalProductID' => $originalProductID // Use original ID in WHERE
                ];
                if ($newImagePath) {
                    $sql .= ", image_path = :image_path";
                    $params[':image_path'] = $newImagePath;
                }

                $sql .= " WHERE productID = :productID";
                $sql .= " WHERE productID = :originalProductID"; // Use original ID to find the row
                $stmt = $db->prepare($sql);

                if ($stmt->execute($params)) {
                    if ($stmt->rowCount() > 0 || $newImagePath || $productID !== $originalProductID) { // Check if ID changed too
                        $_SESSION['admin_product_success'] = "Product '$pname' updated successfully.";
                        if ($newImagePath && $currentImagePath && file_exists($currentImagePath) && $currentImagePath !== $newImagePath) {
                            @unlink($currentImagePath);
                        }
                    } else {
                        $_SESSION['admin_product_success'] = "Product '$pname' data was unchanged.";
                        if ($newImagePath && file_exists($newImagePath)) {
                            @unlink($newImagePath);
                        }
                    }
                } else {
                    $_SESSION['admin_product_error'] = "Failed to update product. Database error.";
                    if ($newImagePath && file_exists($newImagePath)) @unlink($newImagePath); 
                }
                } catch (PDOException $e) {
                    error_log("Admin Update Product DB Error: " . $e->getMessage());
                    $_SESSION['admin_product_error'] = "An unexpected database error occurred during update.";
                    if ($newImagePath && file_exists($newImagePath)) @unlink($newImagePath);
                }
            }
        } else {
            $_SESSION['admin_product_error'] = "Failed to update product:<br>" . implode("<br>", $errors);
            header("Location: index.php?page=admin_manage_products&action=edit&id=" . urlencode($originalProductID)); exit(); // Redirect back using original ID
        }
        header("Location: index.php?page=admin_manage_products"); exit();
    }

    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_product') {
        if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
            $_SESSION['admin_login_notice'] = 'Admin access required.';
            header('Location: index.php?page=admin_login'); exit;
        }

        $productID = trim($_POST['productID'] ?? '');

        if (empty($productID)) {
            $_SESSION['admin_product_error'] = "Product ID is missing for deletion.";
        } else {
            try {
                $stmtGetImage = $db->prepare("SELECT image_path FROM product WHERE productID = ?");
                $stmtGetImage->execute([$productID]);
                $imagePathToDelete = $stmtGetImage->fetchColumn();

                $sql = "DELETE FROM product WHERE productID = :productID";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':productID', $productID);

                if ($stmt->execute()) {
                    if ($stmt->rowCount() > 0) {
                        $_SESSION['admin_product_success'] = "Product ID '$productID' deleted successfully.";
                        if ($imagePathToDelete && file_exists($imagePathToDelete)) {
                            @unlink($imagePathToDelete);
                        }
                    } else {
                        $_SESSION['admin_product_error'] = "Product ID '$productID' not found or already deleted.";
                    }
                } else {
                    $_SESSION['admin_product_error'] = "Failed to delete product. Database error.";
                }
            } catch (PDOException $e) {
                error_log("Admin Delete Product DB Error: " . $e->getMessage());
                if ($e->getCode() == '23000') $_SESSION['admin_product_error'] = "Cannot delete product ID '$productID' as it might be referenced in orders.";
                else $_SESSION['admin_product_error'] = "An unexpected database error occurred.";
            }
        }
        header("Location: index.php?page=admin_manage_products"); exit();
    }

    elseif (isset($_POST['action']) && $_POST['action'] === 'update_bulk_stock') {
        if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
            $_SESSION['admin_login_notice'] = 'Admin access required.';
            header('Location: index.php?page=admin_login'); exit;
        }

        $stockUpdates = $_POST['stock'] ?? [];
        $updatedCount = 0; $errorCount = 0; $errors = [];

        if (empty($stockUpdates) || !is_array($stockUpdates)) {
            $_SESSION['admin_product_error'] = "No stock data received.";
        } else {
            try {
                $db->beginTransaction();
                $sql = "UPDATE product SET stockquantity = :stock WHERE productID = :productID";
                $stmt = $db->prepare($sql);

                foreach ($stockUpdates as $productID => $quantity) {
                    $productID = trim($productID);
                    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

                    if ($quantity === false || $quantity < 0) {
                        $errors[] = "Invalid stock for Product ID: " . htmlspecialchars($productID);
                        $errorCount++; continue;
                    }
                    $stmt->bindParam(':stock', $quantity, PDO::PARAM_INT);
                    $stmt->bindParam(':productID', $productID);
                    if ($stmt->execute()) { if ($stmt->rowCount() > 0) $updatedCount++; }
                    else { $errors[] = "DB error for Product ID: " . htmlspecialchars($productID); $errorCount++; }
                }

                if ($errorCount > 0) {
                    $db->rollBack();
                    $_SESSION['admin_product_error'] = "Failed to update some stock levels:<br>" . implode("<br>", $errors);
                    header("Location: index.php?page=admin_manage_products&action=bulk_edit_stock"); exit();
                } else {
                    $db->commit();
                    $_SESSION['admin_product_success'] = "$updatedCount product stock level(s) updated.";
                }
            } catch (PDOException $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Admin Bulk Stock Update DB Error: " . $e->getMessage());
                $_SESSION['admin_product_error'] = "Database error during bulk stock update.";
                header("Location: index.php?page=admin_manage_products&action=bulk_edit_stock"); exit();
            }
        }
        header("Location: index.php?page=admin_manage_products"); exit();
    }

    elseif (isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
        if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
            $_SESSION['admin_login_notice'] = 'Admin access required.';
            header('Location: index.php?page=admin_login'); exit;
        }

        $orderID = trim($_POST['orderID'] ?? '');
        $newStatus = trim($_POST['status'] ?? '');
        $possibleStatuses = ['Processing', 'Shipped', 'Delivered', 'Cancelled', 'Pending Payment', 'On Hold', 'Paid', 'Unpaid'];

        if (empty($orderID) || empty($newStatus) || !in_array($newStatus, $possibleStatuses)) {
             $_SESSION['admin_order_error'] = "Invalid order ID or status.";
        } else {
             try {
                $sql = "UPDATE orders SET status = :status WHERE orderID = :orderID";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':status', $newStatus);
                $stmt->bindParam(':orderID', $orderID);
                if ($stmt->execute()) {
                    if ($stmt->rowCount() > 0) $_SESSION['admin_order_success'] = "Order ID '$orderID' status updated to '$newStatus'.";
                    else $_SESSION['admin_order_success'] = "Order ID '$orderID' status unchanged or order not found.";
                } else { $_SESSION['admin_order_error'] = "Failed to update order status."; }
             } catch (PDOException $e) {
                 error_log("Admin Update Order Status DB Error: " . $e->getMessage());
                 $_SESSION['admin_order_error'] = "Database error updating order status.";
             }
        }
        header("Location: index.php?page=admin_manage_orders"); exit();
    }

    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_customer') {
        if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
            $_SESSION['admin_login_notice'] = 'Admin access required.';
            header('Location: index.php?page=admin_login'); exit;
        }

        $customerID = trim($_POST['customerID'] ?? '');

        if (empty($customerID)) {
            $_SESSION['admin_customer_error'] = "Customer ID missing.";
        } else {
            try {
                $db->beginTransaction();
                $sql = "DELETE FROM customer WHERE customerID = :customerID";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':customerID', $customerID);

                if ($stmt->execute()) {
                    if ($stmt->rowCount() > 0) $_SESSION['admin_customer_success'] = "Customer ID '$customerID' deleted.";
                    else $_SESSION['admin_customer_error'] = "Customer ID '$customerID' not found.";
                    $db->commit();
                } else { $db->rollBack(); $_SESSION['admin_customer_error'] = "Failed to delete customer."; }
            } catch (PDOException $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Admin Delete Customer DB Error: " . $e->getMessage());
                if ($e->getCode() == '23000') $_SESSION['admin_customer_error'] = "Cannot delete customer ID '$customerID' due to existing related records (e.g., orders).";
                else $_SESSION['admin_customer_error'] = "Database error deleting customer.";
            }
        }
        header("Location: index.php?page=admin_manage_customers"); exit();
    }

    elseif (isset($_POST['add_to_cart_id'])) {
        $productId = filter_input(INPUT_POST, 'add_to_cart_id');
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $customerID = $_SESSION['customerID'] ?? null;

        if ($productId && $quantity && $quantity > 0) {
            try {
                $stmt = $db->prepare("SELECT stockquantity, pname FROM product WHERE productID = ?");
                $stmt->execute([$productId]);
                $productInfo = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($productInfo) {
                    $stock = $productInfo['stockquantity'];
                    $pname = $productInfo['pname'];
                    $cartKey = $productId;
                    $currentQuantity = $_SESSION['cart'][$cartKey] ?? 0;
                    $newQuantity = $currentQuantity + $quantity;

                    if ($newQuantity <= $stock) {
                        $_SESSION['cart'][$cartKey] = $newQuantity;
                        $_SESSION['cart_message'] = htmlspecialchars($pname) . " added to cart.";

                        if ($customerID) {
                            $sql = "INSERT INTO persistent_cart (customerID, productID, quantity) VALUES (:customerID, :productID, :quantity)
                                    ON DUPLICATE KEY UPDATE quantity = :newQuantity";
                            $stmtDb = $db->prepare($sql);
                            $stmtDb->execute([':customerID' => $customerID, ':productID' => $productId, ':quantity' => $quantity, ':newQuantity'=> $newQuantity]);
                        }
                    } else { $_SESSION['cart_error'] = "Cannot add $quantity item(s) of ".htmlspecialchars($pname).". Only $stock available."; }
                } else { $_SESSION['cart_error'] = "Product not found."; }
            } catch (PDOException $e) {
                error_log("Cart Add DB Error (incl. persistent): " . $e->getMessage());
                $_SESSION['cart_error'] = "Error adding item to cart.";
            }
        } else { $_SESSION['cart_error'] = "Invalid product ID or quantity."; }

        $referrer = $_SERVER['HTTP_REFERER'] ?? 'index.php?page=shopping-cart';
        if (strpos($referrer, 'process-payment') !== false || strpos($referrer, 'subscribe') !== false) $referrer = 'index.php?page=shopping-cart';
        header("Location: " . $referrer); exit();
    }
    elseif (isset($_POST['update_quantity_id'])) {
        $productId = filter_input(INPUT_POST, 'update_quantity_id');
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $cartKey = $productId;
        $customerID = $_SESSION['customerID'] ?? null;

        if ($productId && isset($_SESSION['cart'][$cartKey]) && $quantity !== false) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$cartKey]);
                $_SESSION['cart_message'] = "Item removed.";
                if ($customerID) {
                    try { $stmtDb = $db->prepare("DELETE FROM persistent_cart WHERE customerID = ? AND productID = ?"); $stmtDb->execute([$customerID, $productId]); }
                    catch (PDOException $e) { error_log("Cart Remove (Update 0) DB Error: ".$e->getMessage()); }
                }
            } else {
                try {
                    $stmt = $db->prepare("SELECT stockquantity, pname FROM product WHERE productID = ?");
                    $stmt->execute([$productId]);
                    $productInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($productInfo) {
                        $stock = $productInfo['stockquantity']; $pname = $productInfo['pname'];
                        $finalQuantity = ($quantity > $stock) ? $stock : $quantity;
                        if ($quantity > $stock) $_SESSION['cart_error'] = "Quantity for ".htmlspecialchars($pname)." adjusted to max stock ($stock).";
                        else $_SESSION['cart_message'] = "Cart updated.";
                        $_SESSION['cart'][$cartKey] = $finalQuantity;
                        if ($customerID) {
                            $sql = "INSERT INTO persistent_cart (customerID, productID, quantity) VALUES (:customerID, :productID, :quantity) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)";
                            $stmtDb = $db->prepare($sql); $stmtDb->execute([':customerID' => $customerID, ':productID' => $productId, ':quantity' => $finalQuantity]);
                        }
                    } else {
                        unset($_SESSION['cart'][$cartKey]); $_SESSION['cart_error'] = "Item removed (no longer available).";
                        if ($customerID) {
                            try { $stmtDb = $db->prepare("DELETE FROM persistent_cart WHERE customerID = ? AND productID = ?"); $stmtDb->execute([$customerID, $productId]); }
                            catch (PDOException $e) { error_log("Cart Remove (Product Gone) DB Error: ".$e->getMessage()); }
                        }
                    }
                } catch (PDOException $e) { error_log("Cart Update DB Error (incl. persistent): " . $e->getMessage()); $_SESSION['cart_error'] = "Error updating cart."; }
            }
        } else { $_SESSION['cart_error'] = "Invalid request to update cart."; }
        header("Location: index.php?page=shopping-cart"); exit();
    }
    elseif (isset($_POST['remove_item_id'])) {
        $productId = filter_input(INPUT_POST, 'remove_item_id', FILTER_SANITIZE_STRING);
        $cartKey = $productId;
        $customerID = $_SESSION['customerID'] ?? null;
        if ($productId && isset($_SESSION['cart'][$cartKey])) {
            unset($_SESSION['cart'][$cartKey]); $_SESSION['cart_message'] = "Item removed.";
            if ($customerID) {
                 try { $stmtDb = $db->prepare("DELETE FROM persistent_cart WHERE customerID = ? AND productID = ?"); $stmtDb->execute([$customerID, $productId]); }
                 catch (PDOException $e) { error_log("Cart Remove DB Error: ".$e->getMessage()); }
            }
        } else { $_SESSION['cart_error'] = "Invalid request to remove item."; }
        header("Location: index.php?page=shopping-cart"); exit();
    }
    elseif (isset($_POST['clear_cart'])) {
        $customerID = $_SESSION['customerID'] ?? null;
        $_SESSION['cart'] = []; $_SESSION['cart_message'] = "Cart cleared.";
        if ($customerID) {
            try { $stmtDb = $db->prepare("DELETE FROM persistent_cart WHERE customerID = ?"); $stmtDb->execute([$customerID]); }
            catch (PDOException $e) { error_log("Cart Clear DB Error: ".$e->getMessage()); }
        }
        header("Location: index.php?page=shopping-cart"); exit();
    }

    elseif ($page === 'edit_profile' && isset($_SESSION['customerID'])) {
        $customerID = $_SESSION['customerID'];
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $profilePictureFile = $_FILES['profile_picture'] ?? null;
        $errors = []; $formData = $_POST; $uploadPath = null; $oldPicturePath = null;

        if (empty($name)) $errors[] = "Name is required.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
        else {
            try { $stmt = $db->prepare("SELECT customerID FROM customer WHERE email = ? AND customerID != ?"); $stmt->execute([$email, $customerID]); if ($stmt->fetch()) $errors[] = "Email already in use."; }
            catch (PDOException $e) { $errors[] = "DB error checking email."; error_log("Edit Profile Email Check DB Error: " . $e->getMessage()); }
        }
        if (!empty($birthdate)) { $minAgeDate = date('Y-m-d', strtotime('-18 years')); if ($birthdate > $minAgeDate) $errors[] = "Must be 18+."; }
        if (strlen($address) > 500) $errors[] = "Address too long.";
        if (!empty($phone) && !preg_match('/^\+?[0-9\s\-()]{8,20}$/', $phone)) $errors[] = "Invalid phone format.";

        if ($profilePictureFile && $profilePictureFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/images/profile_pictures/';
            if (!is_dir($uploadDir)) { if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) $errors[] = "Failed to create upload dir."; }
            if (empty($errors)) {
                $fileName = $customerID . '_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($profilePictureFile['name']));
                $targetFile = $uploadDir . $fileName; $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION)); $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                $check = @getimagesize($profilePictureFile['tmp_name']);
                if ($check === false) $errors[] = "File not an image.";
                elseif ($profilePictureFile['size'] > 5 * 1024 * 1024) $errors[] = "Image too large (Max 5MB).";
                elseif (!in_array($imageFileType, $allowedTypes)) $errors[] = "Invalid image type.";
                else {
                    try { $stmtOldPic = $db->prepare("SELECT profile_picture FROM customer WHERE customerID = ?"); $stmtOldPic->execute([$customerID]); $oldPicturePath = $stmtOldPic->fetchColumn(); } catch (PDOException $e) {}
                    if (move_uploaded_file($profilePictureFile['tmp_name'], $targetFile)) $uploadPath = $targetFile;
                    else $errors[] = "Failed to upload picture.";
                }
            }
        } elseif ($profilePictureFile && $profilePictureFile['error'] !== UPLOAD_ERR_NO_FILE) $errors[] = "File upload error: Code " . $profilePictureFile['error'];

        if (empty($errors)) {
            try {
                $sql = "UPDATE customer SET name = :name, email = :email, phone = :phone, address = :address, birthdate = :birthdate";
                $params = [':name' => $name, ':email' => $email, ':phone' => $phone ?: null, ':address' => $address ?: null, ':birthdate' => $birthdate ?: null];
                if ($uploadPath) { $sql .= ", profile_picture = :profile_picture"; $params[':profile_picture'] = $uploadPath; }
                $sql .= " WHERE customerID = :customerID"; $params[':customerID'] = $customerID;
                $stmt = $db->prepare($sql); $stmt->execute($params);
                $_SESSION['name'] = $name; $_SESSION['email'] = $email;
                if ($uploadPath && $oldPicturePath && file_exists($oldPicturePath) && $oldPicturePath !== $uploadPath) @unlink($oldPicturePath);
                $_SESSION['success_message'] = 'Profile updated successfully.';
                header('Location: index.php?page=customer_profile&update=success'); exit;
            } catch (PDOException $e) {
                error_log("Edit Profile Update DB Error: " . $e->getMessage());
                $_SESSION['edit_profile_error'] = "DB error updating profile."; $_SESSION['form_data'] = $formData;
                if ($uploadPath && file_exists($uploadPath)) @unlink($uploadPath);
            }
        } else { $_SESSION['edit_profile_error'] = implode("<br>", $errors); $_SESSION['form_data'] = $formData; }
        header('Location: index.php?page=edit_profile'); exit();
    }

    elseif ($page === 'change_password' && isset($_SESSION['customerID'])) {
        $customerID = $_SESSION['customerID'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $errorMessage = null;

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) $errorMessage = "All fields required.";
        elseif ($newPassword !== $confirmPassword) $errorMessage = "Passwords don't match.";
        elseif (strlen($newPassword) < 8) $errorMessage = "New password must be at least 8 characters long.";
        // *** ADDED: Server-side special character validation ***
        elseif (!preg_match('/[!@#$%^&*()_+=\-[\]{};\':"\\|,.<>\/?]/', $newPassword)) $errorMessage = "New password must contain at least one special character (e.g., !@#$%^&*).";
        else {
            try {
                $stmt = $db->prepare("SELECT password FROM customer WHERE customerID = ?"); $stmt->execute([$customerID]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($currentPassword, $user['password'])) {
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT); if ($newPasswordHash === false) throw new Exception("Hashing failed.");
                    $stmtUpdate = $db->prepare("UPDATE customer SET password = ? WHERE customerID = ?"); $stmtUpdate->execute([$newPasswordHash, $customerID]);
                    $_SESSION['success_message'] = 'Password changed successfully.';
                    header('Location: index.php?page=customer_profile&password=changed'); exit;
                } else { $errorMessage = "Current password incorrect."; }
            } catch (PDOException $e) { error_log("Change Password POST DB Error: " . $e->getMessage()); $errorMessage = "DB error changing password."; }
            catch (Exception $e) { error_log("Change Password POST Error: " . $e->getMessage()); $errorMessage = "Unexpected error."; }
        }
        if ($errorMessage) { $_SESSION['change_password_error'] = $errorMessage; header('Location: index.php?page=change_password'); exit(); }
    }

    elseif ($page === 'admin_edit_profile' && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true) {
        $adminID = $_SESSION['adminID'];
        $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
        $errors = []; $formData = $_POST;

        if (empty($name)) $errors[] = "Name required.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
        else {
            try { $stmt = $db->prepare("SELECT adminID FROM admin WHERE email = ? AND adminID != ?"); $stmt->execute([$email, $adminID]); if ($stmt->fetch()) $errors[] = "Email already used by another admin."; }
            catch (PDOException $e) { $errors[] = "DB error checking email."; error_log("Admin Edit Profile Email Check DB Error: " . $e->getMessage()); }
        }

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("UPDATE admin SET name = :name, email = :email WHERE adminID = :adminID");
                $stmt->execute([':name' => $name, ':email' => $email, ':adminID' => $adminID]);
                $_SESSION['adminName'] = $name;
                $_SESSION['admin_success_message'] = 'Admin profile updated.';
                header('Location: index.php?page=admin_profile&update=success'); exit;
            } catch (PDOException $e) { error_log("Admin Edit Profile Update DB Error: " . $e->getMessage()); $_SESSION['admin_edit_profile_error'] = "DB error updating admin profile."; $_SESSION['admin_form_data'] = $formData; }
        } else { $_SESSION['admin_edit_profile_error'] = implode("<br>", $errors); $_SESSION['admin_form_data'] = $formData; }
        header('Location: index.php?page=admin_edit_profile'); exit();
    }

    elseif ($page === 'admin_change_password' && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true) {
        $adminID = $_SESSION['adminID'];
        $currentPassword = $_POST['current_password'] ?? ''; $newPassword = $_POST['new_password'] ?? ''; $confirmPassword = $_POST['confirm_password'] ?? '';
        $errorMessage = null;

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) $errorMessage = "All fields required.";
        elseif ($newPassword !== $confirmPassword) $errorMessage = "Passwords don't match.";
        elseif (strlen($newPassword) < 8) $errorMessage = "Password too short (min 8 chars).";
        else {
            try {
                $stmt = $db->prepare("SELECT password FROM admin WHERE adminID = ?"); $stmt->execute([$adminID]); $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($adminData && password_verify($currentPassword, $adminData['password'])) {
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT); if ($newPasswordHash === false) throw new Exception("Hashing failed.");
                    $stmtUpdate = $db->prepare("UPDATE admin SET password = ? WHERE adminID = ?"); $stmtUpdate->execute([$newPasswordHash, $adminID]);
                    $_SESSION['admin_success_message'] = 'Admin password changed.';
                    header('Location: index.php?page=admin_profile&password=changed'); exit;
                } else { $errorMessage = "Current password incorrect."; }
            } catch (PDOException $e) { error_log("Admin Change Password POST DB Error: " . $e->getMessage()); $errorMessage = "DB error changing password."; }
            catch (Exception $e) { error_log("Admin Change Password POST Error: " . $e->getMessage()); $errorMessage = "Unexpected error."; }
        }
        if ($errorMessage) { $_SESSION['admin_change_password_error'] = $errorMessage; header('Location: index.php?page=admin_change_password'); exit(); }
    }

    elseif ($page === 'process-payment') {
        if (!isset($_SESSION['customerID'])) {
             $_SESSION['login_notice'] = 'Please log in to complete your order.';
             $_SESSION['redirect_after_login'] = 'index.php?page=checkout';
             header('Location: index.php?page=login_register&notice=login_required'); exit;
        }
        if (empty($_SESSION['cart'])) {
             header('Location: index.php?page=shopping-cart&notice=cart_empty'); exit;
        }

        $shippingName = trim($_POST['name'] ?? '');
        $shippingAddress = trim($_POST['address'] ?? '');
        $shippingPhone = trim($_POST['phone'] ?? '');
        $cardNumber = $_POST['card_number'] ?? null; 
        $expiry = $_POST['expiry'] ?? null;        
        $cvv = $_POST['cvv'] ?? null;               
        $checkoutErrors = [];

        if (empty($shippingName)) $checkoutErrors[] = "Shipping name required.";
        if (empty($shippingAddress)) $checkoutErrors[] = "Shipping address required.";
        if (empty($cardNumber) || !preg_match('/^\d{12}$/', preg_replace('/\s+/', '', $cardNumber))) $checkoutErrors[] = "Invalid card number (12 digits).";
        if (empty($expiry) || !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) $checkoutErrors[] = "Invalid expiry date (MM/YY).";
        if (empty($cvv) || !preg_match('/^\d{3,4}$/', $cvv)) $checkoutErrors[] = "Invalid CVV.";

        if (empty($checkoutErrors)) {
            $paymentSuccessful = true; 
            if ($paymentSuccessful) {
                try {
                    $db->beginTransaction();
                    $cartItemsDetails = []; $totalPrice = 0; $productIDs = array_keys($_SESSION['cart']);
                    if (empty($productIDs)) throw new Exception("Cart is empty.");

                    $placeholders = implode(',', array_fill(0, count($productIDs), '?'));
                    $stmtCart = $db->prepare("SELECT productID, pname, price, stockquantity FROM product WHERE productID IN ($placeholders) FOR UPDATE");
                    $stmtCart->execute($productIDs);
                    $productsFromDB = []; while ($row = $stmtCart->fetch(PDO::FETCH_ASSOC)) { $productsFromDB[$row['productID']] = $row; }
                    foreach ($_SESSION['cart'] as $id => $quantity) {
                        if (!isset($productsFromDB[$id])) throw new Exception("Product ID $id not found.");
                        $productData = $productsFromDB[$id];
                        if ($quantity <= 0) throw new Exception("Invalid quantity for product ID $id.");
                        if ($quantity > $productData['stockquantity']) throw new Exception("Insufficient stock for '".htmlspecialchars($productData['pname'])."' (Req: $quantity, Avail: {$productData['stockquantity']}).");
                        $cartItemsDetails[$id] = ['id' => $id, 'name' => $productData['pname'], 'price' => $productData['price'], 'quantity' => $quantity, 'subtotal' => $productData['price'] * $quantity];
                        $totalPrice += $cartItemsDetails[$id]['subtotal'];
                    }

                    $orderID = 'ORD-' . strtoupper(uniqid()); $customerID = $_SESSION['customerID']; $orderDate = date('Y-m-d H:i:s'); $orderStatus = 'Processing'; $paymentStatus = 'Paid';
                    $stmtOrderHeader = $db->prepare("INSERT INTO orders (orderID, customerID, order_date, total_amount, status, shipping_name, shipping_address, shipping_phone, payment_status) VALUES (:orderID, :customerID, :order_date, :total_amount, :status, :shipping_name, :shipping_address, :shipping_phone, :payment_status)");
                    $stmtOrderHeader->execute([
                        ':orderID' => $orderID, ':customerID' => $customerID, ':order_date' => $orderDate,
                        ':total_amount' => $totalPrice, ':status' => $orderStatus, ':shipping_name' => $shippingName,
                        ':shipping_address' => $shippingAddress, ':shipping_phone' => $shippingPhone ?: null, ':payment_status' => $paymentStatus
                    ]);

                    $stmtOrderItem = $db->prepare("INSERT INTO order_items (orderID, productID, quantity, price_at_purchase) VALUES (:orderID, :productID, :quantity, :price_at_purchase)");
                    $stmtStock = $db->prepare("UPDATE product SET stockquantity = stockquantity - :updateQuantity WHERE productID = :productID AND stockquantity >= :checkQuantity");

                    foreach ($cartItemsDetails as $item) {
                        $stmtOrderItem->execute([
                            ':orderID' => $orderID, ':productID' => $item['id'],
                            ':quantity' => $item['quantity'], ':price_at_purchase' => $item['price']
                        ]);

                        $stmtStock->bindParam(':updateQuantity', $item['quantity'], PDO::PARAM_INT);
                        $stmtStock->bindParam(':checkQuantity', $item['quantity'], PDO::PARAM_INT); // Bind same value
                        $stmtStock->bindParam(':productID', $item['id']);
                        $updated = $stmtStock->execute();

                        if (!$updated || $stmtStock->rowCount() === 0) {
                            throw new Exception("Stock update failed for product ID {$item['id']}. Stock may have changed.");
                        }
                    }

                    $db->commit();

                    $_SESSION['cart'] = [];
                    try {
                        $stmtClear = $db->prepare("DELETE FROM persistent_cart WHERE customerID = ?");
                        $stmtClear->execute([$customerID]);
                    } catch (PDOException $e) {
                        error_log("Checkout Success - Clear Persistent Cart DB Error: " . $e->getMessage());
                    }

                    $_SESSION['last_order_id'] = $orderID;
                    $_SESSION['last_order_total'] = $totalPrice;

                    header('Location: index.php?page=order-confirmation&status=success');
                    exit;

                } catch (PDOException | Exception $e) { 
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log("Order Creation Failed: " . $e->getMessage());
                    $_SESSION['checkout_error'] = 'Failed to place order: ' . $e->getMessage();
                    header('Location: index.php?page=checkout');
                    exit;
                }
            } else {
                $_SESSION['checkout_error'] = 'Payment failed.';
                header('Location: index.php?page=checkout');
                exit;
            }
        } else {
            $_SESSION['checkout_error'] = implode("<br>", $checkoutErrors);
            header('Location: index.php?page=checkout');
            exit;
        }
    }

    elseif ($page === 'contact') {
        $name = trim($_POST['contact_name'] ?? ''); $email = trim($_POST['contact_email'] ?? ''); $subject = trim($_POST['contact_subject'] ?? ''); $message = trim($_POST['contact_message'] ?? '');
        $errors = [];
        if (empty($name)) $errors[] = "Name required.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
        if (empty($subject)) $errors[] = "Subject required.";
        if (empty($message) || strlen($message) < 10) $errors[] = "Message required (min 10 chars).";

        if (empty($errors)) {
            $_SESSION['contact_success'] = "Thank you for contacting us!";
        } else { $_SESSION['contact_error'] = implode("<br>", $errors); $_SESSION['contact_form_data'] = $_POST; }
        header("Location: index.php?page=contact"); exit();
    }

    elseif ($page === 'subscribe') {
        $newsletter_email = filter_input(INPUT_POST, 'newsletter_email', FILTER_VALIDATE_EMAIL);
        if ($newsletter_email) {
            $_SESSION['newsletter_message'] = "Thanks for subscribing!";
        } else { $_SESSION['newsletter_message'] = "Please enter a valid email."; }
        $referrer = $_SERVER['HTTP_REFERER'] ?? 'index.php?page=home';
        if (strpos($referrer, 'process-payment') !== false || strpos($referrer, 'subscribe') !== false) $referrer = 'index.php?page=home';
        header("Location: " . $referrer); exit();
    }

}
require_once 'header.php';
try {
    $allowedPages = [
        'home', 'login_register', 'customer_profile', 'edit_profile', 'change_password',
        'product-listing', 'product-detail', 'shopping-cart', 'checkout',
        'process-payment', 'logout', 'admin_login',
        'admin_dashboard', 'admin_manage_products', 'admin_manage_customers', 'admin_manage_orders',
        'admin_profile', 'admin_edit_profile', 'admin_change_password',
        'contact', 'about', 'faq', 'order-confirmation',
        'privacy', 'terms', 'shipping', 'subscribe', 'search',
        'order_history', 'order_details',
    ];

    $adminPages = [
        'admin_dashboard', 'admin_manage_products', 'admin_manage_customers',
        'admin_manage_orders', 'admin_profile', 'admin_edit_profile', 'admin_change_password'
    ];
    if (in_array($page, $adminPages) && (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true)) {
        $_SESSION['admin_login_notice'] = 'Admin access required.';
        $_SESSION['redirect_after_admin_login'] = $_SERVER['REQUEST_URI'];
        header('Location: index.php?page=admin_login&notice=admin_required');
        exit;
    }

    if ($page === 'logout') {
        $isCustomerLogout = isset($_SESSION['isCustomer']) && $_SESSION['isCustomer'] === true;
        $customerID = $_SESSION['customerID'] ?? null;
        $cartToSave = $_SESSION['cart'] ?? [];
        $isAdminLogout = isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;
        if ($isCustomerLogout && $customerID && !empty($cartToSave)) {
            try {
                $sql = "INSERT INTO persistent_cart (customerID, productID, quantity) VALUES (:customerID, :productID, :quantity) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)";
                $stmtSave = $db->prepare($sql);
                $db->beginTransaction();
                foreach ($cartToSave as $productID => $quantity) {
                    if ($quantity > 0) $stmtSave->execute([':customerID' => $customerID, ':productID' => $productID, ':quantity' => $quantity]);
                }
                $db->commit();
            } catch (PDOException $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Logout Cart Save DB Error: " . $e->getMessage());
            }
        }

        session_unset(); session_destroy(); session_start(); 
        if ($isAdminLogout) { $_SESSION['admin_login_message'] = 'Logged out.'; header('Location: index.php?page=admin_login'); }
        else { $_SESSION['login_message'] = 'Logged out successfully.'; header('Location: index.php?page=login_register&logout=success'); }
        exit;
    }

    if (($page === 'process-payment' || $page === 'subscribe') && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=home'); exit;
    }

    if (!in_array($page, $allowedPages)) {
        error_log("Attempted access to invalid page: " . $page);
        $page = 'home'; $currentPagePHP = 'home'; 
    }

    $filePath = $page . '.php';

    if (file_exists($filePath)) {
        include $filePath;
    } else {
        error_log("File not found for allowed page: " . $filePath);
        http_response_code(404);
        if (file_exists('404.php')) include '404.php';
        else echo "<div class='container' style='padding-top: 20px; text-align: center;'><h2>404 - Page Not Found</h2><p>Sorry, the page file (<code>".htmlspecialchars($filePath)."</code>) could not be found.</p></div>";
    }

} catch (PDOException $e) {
    error_log("Page Display DB Error (" . $page . "): " . $e->getMessage());
    http_response_code(500);
    echo "<div class='container alert alert-danger'>Database error loading page.</div>";
} catch (Exception $e) {
    error_log("Page Display General Error (" . $page . "): " . $e->getMessage());
    http_response_code(500);
    echo "<div class='container alert alert-danger'>Unexpected error loading page.</div>";
}

require_once 'footer.php';

?>
