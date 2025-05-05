<?php
if (!isset($_SESSION['customerID'])) {
    $_SESSION['redirect_after_login'] = 'index.php?page=edit_profile'; 
    $_SESSION['login_notice'] = 'Please log in to edit your profile.';
    header('Location: index.php?page=login_register&notice=login_required'); 
    exit;
}

$customerID = $_SESSION['customerID'];
$customer = null;
$fetchError = null;

try {
    $stmt = $db->prepare("SELECT customerID, name, email, address, birthdate, phone, profile_picture FROM customer WHERE customerID = ?");
    $stmt->execute([$customerID]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Edit Profile Fetch Error: " . $e->getMessage());
    $fetchError = "Could not load profile data for editing.";
}

if (!$customer && !$fetchError) {
    session_destroy();
    header('Location: index.php?page=login_register&error=user_not_found');
    exit;
}

$errors = $_SESSION['edit_profile_error'] ?? null;
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['edit_profile_error'], $_SESSION['form_data']); 

$display_name = $formData['name'] ?? $customer['name'] ?? '';
$display_email = $formData['email'] ?? $customer['email'] ?? '';
$display_address = $formData['address'] ?? $customer['address'] ?? '';
$display_birthdate = $formData['birthdate'] ?? $customer['birthdate'] ?? '';
$display_phone = $formData['phone'] ?? $customer['phone'] ?? '';
$display_profile_picture = $customer['profile_picture'] ?? null;

?>

<div class="profile-container">
    <div class="profile-sidebar">
        <div class="profile-picture-container">
            <?php if (!empty($display_profile_picture) && file_exists($display_profile_picture)): ?>
                <img src="<?php echo htmlspecialchars($display_profile_picture); ?>?t=<?php echo time(); ?>" alt="Profile Picture" class="profile-picture">
            <?php else: ?>
                <div class="default-profile">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="profile-name"><?php echo htmlspecialchars($customer['name'] ?? 'User'); ?></h2>
        <p class="profile-email"><?php echo htmlspecialchars($customer['email'] ?? 'No email'); ?></p>

        <ul class="profile-nav">
            <li class="profile-nav-item">
                <a href="index.php?page=customer_profile" class="profile-nav-link">
                    <i class="fas fa-user"></i> Profile Information
                </a>
            </li>
            <li class="profile-nav-item">
                <a href="index.php?page=edit_profile" class="profile-nav-link active">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </li>
            <li class="profile-nav-item">
                <a href="index.php?page=change_password" class="profile-nav-link">
                    <i class="fas fa-key"></i> Change Password
                </a>
            </li>
            
        </ul>
    </div>
    <div class="profile-content">
        <?php if ($fetchError): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div>
        <?php else: ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php echo $errors; ?>
                </div>
            <?php endif; ?>

            <h1 class="section-title">Edit Profile</h1>
            <form action="index.php?page=edit_profile" method="post" id="edit-profile-form" enctype="multipart/form-data">
                <div class="form-group profile-pic-edit">
                    <label>Profile Picture</label>
                    <div class="profile-pic-preview">
                        <?php if (!empty($display_profile_picture) && file_exists($display_profile_picture)): ?>
                            <img src="<?php echo htmlspecialchars($display_profile_picture); ?>?t=<?php echo time(); ?>" alt="Profile Picture" id="profile-preview">
                            <div class="default-preview" id="profile-preview-default" style="display: none;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php else: ?>
                            <div class="default-preview" id="profile-preview-default">
                                <i class="fas fa-user"></i>
                            </div>
                            <img src="" alt="Profile Preview" id="profile-preview" style="display: none;">
                        <?php endif; ?>
                        <div class="profile-pic-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Change Picture</span>
                        </div>
                    </div>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="profile-pic-input">
                    <span class="error-message" id="profile-picture-error"></span>
                    <span class="hint">Allowed formats: JPG, JPEG, PNG, GIF. Max size: 5MB</span>
                </div>

                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($display_name); ?>" required>
                    <span class="error-message" id="name-error"></span>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($display_email); ?>" required>
                    <span class="error-message" id="email-error"></span>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($display_phone); ?>" placeholder="e.g., +60 12-345-6789">
                    <span class="error-message" id="phone-error"></span>
                    <span class="hint">Format: Country code and number (e.g., +60 12-345-6789)</span>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($display_address); ?></textarea>
                    <span class="error-message" id="address-error"></span>
                    <span class="char-count"><span id="address-char-count">0</span>/500 characters</span>
                </div>

                <div class="form-group">
                    <label for="birthdate">Birth Date <span class="hint">(Must be 18+ years old)</span></label>
                    <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($display_birthdate); ?>" max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                    <span class="error-message" id="birthdate-error"></span>
                </div>

                <div class="action-buttons">
                    <button type="submit" id="save-changes-btn" class="primary-btn">Save Changes</button>
                    <a href="index.php?page=profile" class="secondary-btn">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addressInput = document.getElementById('address');
    const profilePicInput = document.getElementById('profile_picture');
    const profilePreview = document.getElementById('profile-preview');
    const profilePreviewDefault = document.getElementById('profile-preview-default');
    const profilePicPreview = document.querySelector('.profile-pic-preview');
    const profilePicError = document.getElementById('profile-picture-error');
    const addressCharCount = document.getElementById('address-char-count');

    if (addressInput && addressCharCount) {
        addressCharCount.textContent = addressInput.value.length;
        addressInput.addEventListener('input', function() {
            addressCharCount.textContent = this.value.length;
        });
    }

    if (profilePicPreview && profilePicInput) {
        profilePicPreview.addEventListener('click', function() {
            profilePicInput.click();
        });

        profilePicInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const fileType = file.type;
                const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];

                if (!validImageTypes.includes(fileType)) {
                    profilePicError.textContent = 'Please select a valid image file (JPG, JPEG, PNG, GIF).';
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    profilePicError.textContent = 'Image size should not exceed 5MB.';
                    return;
                }
                profilePicError.textContent = '';

                const reader = new FileReader();
                reader.onload = function(e) {
                    if (profilePreviewDefault) profilePreviewDefault.style.display = 'none';
                    if (profilePreview) {
                        profilePreview.style.display = 'block';
                        profilePreview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-profile-form');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const addressInput = document.getElementById('address');
    const birthdateInput = document.getElementById('birthdate');
    const profilePicInput = document.getElementById('profile_picture');
    const profilePreview = document.getElementById('profile-preview');
    const profilePreviewDefault = document.getElementById('profile-preview-default');
    const profilePicPreview = document.querySelector('.profile-pic-preview');
    const saveBtn = document.getElementById('save-changes-btn');
    const confirmModal = document.getElementById('confirm-modal');
    const confirmSaveBtn = document.getElementById('confirm-save');
    const closeModalBtns = document.querySelectorAll('.close-modal');

    const nameError = document.getElementById('name-error');
    const emailError = document.getElementById('email-error');
    const phoneError = document.getElementById('phone-error');
    const addressError = document.getElementById('address-error');
    const birthdateError = document.getElementById('birthdate-error');
    const profilePicError = document.getElementById('profile-picture-error');
    const addressCharCount = document.getElementById('address-char-count');

    if (addressInput && addressCharCount) {
        addressCharCount.textContent = addressInput.value.length;

        addressInput.addEventListener('input', function() {
            addressCharCount.textContent = this.value.length;
            
            if (this.value.length > 500) {
                addressError.textContent = 'Address cannot exceed 500 characters.';
                addressInput.classList.add('error');
            } else {
                addressError.textContent = '';
                addressInput.classList.remove('error');
            }
        });
    }

    if (profilePicPreview && profilePicInput) {
        profilePicPreview.addEventListener('click', function() {
            profilePicInput.click();
        });

        
                profilePicError.textContent = '';
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (profilePreviewDefault) {
                        profilePreviewDefault.style.display = 'none';
                    }
                    profilePreview.style.display = 'block';
                    profilePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    

    function validateName() {
        if (!nameInput.value.trim()) {
            nameError.textContent = 'Name is required.';
            nameInput.classList.add('error');
            return false;
        } else if (nameInput.value.trim().length < 2) {
            nameError.textContent = 'Name must be at least 2 characters long.';
            nameInput.classList.add('error');
            return false;
        } else if (nameInput.value.trim().length > 50) {
            nameError.textContent = 'Name cannot exceed 50 characters.';
            nameInput.classList.add('error');
            return false;
        } else {
            nameError.textContent = '';
            nameInput.classList.remove('error');
            return true;
        }
    }
    
    function validateEmail() {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!emailInput.value.trim()) {
            emailError.textContent = 'Email is required.';
            emailInput.classList.add('error');
            return false;
        } else if (!emailPattern.test(emailInput.value.trim())) {
            emailError.textContent = 'Please enter a valid email address.';
            emailInput.classList.add('error');
            return false;
        } else if (emailInput.value.trim().length > 100) {
            emailError.textContent = 'Email cannot exceed 100 characters.';
            emailInput.classList.add('error');
            return false;
        } else {
            emailError.textContent = '';
            emailInput.classList.remove('error');
            return true;
        }
    }
    
    function validatePhone() {
        if (phoneInput.value.trim()) {
            const cleanPhone = phoneInput.value.replace(/[^0-9]/g, '');
            
            if (cleanPhone.length < 8 || cleanPhone.length > 15) {
                phoneError.textContent = 'Phone number must be between 8 and 15 digits.';
                phoneInput.classList.add('error');
                return false;
            }
        }
        
        phoneError.textContent = '';
        phoneInput.classList.remove('error');
        return true;
    }
    
    function validateAddress() {
        if (addressInput.value.trim().length > 500) {
            addressError.textContent = 'Address cannot exceed 500 characters.';
            addressInput.classList.add('error');
            return false;
        }
        
        addressError.textContent = '';
        addressInput.classList.remove('error');
        return true;
    }
    
    function validateBirthdate() {
        if (birthdateInput.value) {
            const selectedDate = new Date(birthdateInput.value);
            const today = new Date();
            
            // Calculate age
            let age = today.getFullYear() - selectedDate.getFullYear();
            const monthDiff = today.getMonth() - selectedDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < selectedDate.getDate())) {
                age--;
            }
            
            if (selectedDate > today) {
                birthdateError.textContent = 'Birth date cannot be in the future.';
                birthdateInput.classList.add('error');
                return false;
            } else if (age < 18) {
                birthdateError.textContent = 'You must be at least 18 years old.';
                birthdateInput.classList.add('error');
                return false;
            }
        }
        
        birthdateError.textContent = '';
        birthdateInput.classList.remove('error');
        return true;
    }
    
    if (nameInput) {
        nameInput.addEventListener('input', validateName);
        nameInput.addEventListener('blur', validateName);
    }
    
    if (emailInput) {
        emailInput.addEventListener('input', validateEmail);
        emailInput.addEventListener('blur', validateEmail);
    }
    
    if (phoneInput) {
        phoneInput.addEventListener('input', validatePhone);
        phoneInput.addEventListener('blur', validatePhone);
    }
    
    if (birthdateInput) {
        birthdateInput.addEventListener('change', validateBirthdate);
        birthdateInput.addEventListener('blur', validateBirthdate);
    }
    
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const isNameValid = validateName();
            const isEmailValid = validateEmail();
            const isPhoneValid = validatePhone();
            const isAddressValid = validateAddress();
            const isBirthdateValid = validateBirthdate();
            
            if (isNameValid && isEmailValid && isPhoneValid && isAddressValid && isBirthdateValid) {
                confirmModal.style.display = 'flex';
            } else {
                if (!isNameValid) nameInput.focus();
                else if (!isEmailValid) emailInput.focus();
                else if (!isPhoneValid) phoneInput.focus();
                else if (!isAddressValid) addressInput.focus();
                else if (!isBirthdateValid) birthdateInput.focus();
            }
        });
    }

    if (confirmSaveBtn) {
        confirmSaveBtn.addEventListener('click', function() {
            form.submit();
        });
    }

    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            confirmModal.style.display = 'none';
        });
    });
    
    window.addEventListener('click', function(event) {
        if (event.target === confirmModal) {
            confirmModal.style.display = 'none';
        }
    });
});
</script>