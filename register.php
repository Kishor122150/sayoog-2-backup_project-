<?php
require_once 'config.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$name = '';
$email = '';
$address = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($address)) $errors[] = "Address is required.";
    
    // Nepal Phone Validation
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!validate_nepal_phone($phone)) {
        $errors[] = "Invalid Nepal phone number. Use 10-digit mobile (starting with 98, 97, 96) or 9-digit landline (starting with 01).";
    }

    // Password Validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (!validate_password($password)) {
        $errors[] = "Password does not meet the strength requirements.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email is already registered. Please log in.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    // Register User
    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Handle profile photo upload
            $profile_photo = null;
            if (!empty($_FILES['profile_photo']['name'])) {
                $photoFile = $_FILES['profile_photo'];
                if ($photoFile['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($photoFile['tmp_name']);
                    if (in_array($mime, $allowedTypes, true) && $photoFile['size'] <= 2 * 1024 * 1024) {
                        $extension = pathinfo($photoFile['name'], PATHINFO_EXTENSION);
                        $filename = 'profile_' . uniqid() . '.' . strtolower($extension);
                        $destination = UPLOADS_DIR . '/' . $filename;
                        if (move_uploaded_file($photoFile['tmp_name'], $destination)) {
                            $profile_photo = 'uploads/' . $filename;
                        }
                    }
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, address, phone, password, role, profile_photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $address, $phone, $password_hash, 'user', $profile_photo]);
            
            // Send welcome email notification
            $new_user_id = $pdo->lastInsertId();
            create_notification($pdo, $new_user_id, 'registration',
                'Welcome to Sayog, ' . $name . '! Your account has been created successfully. You can now browse and donate food in your community.',
                'login.php', true);
            
            // Set success flash message and redirect
            set_flash_message('success', 'Registration successful! You can now log in.');
            redirect('login.php');
        } catch (PDOException $e) {
            $errors[] = "Failed to register user: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Sayog - Food Donation System</title>
    <link rel="stylesheet" href="style.css">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/app.js"></script>
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <a href="#" class="auth-logo">
                <div class="auth-logo-icon">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
                <span>SAYOG</span>
            </a>
            <p class="auth-subtitle">Connecting surplus food with those in need</p>
            <div style="display:flex;gap:8px;justify-content:center;margin-top:12px;">
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <button class="lang-toggle" onclick="toggleLanguage()" style="background:rgba(59,130,246,0.1);padding:6px 14px;border-radius:999px;border:1px solid var(--border);cursor:pointer;font-size:12px;font-weight:600;color:var(--text-secondary);">
                    <span>नेपाली</span>
                </button>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div>
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Error:</strong>
                    <ul style="margin-top: 5px; padding-left: 20px; font-size: 13px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm" enctype="multipart/form-data" novalidate>
            <!-- Name & Email -->
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="E.g., Ram Bahadur" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="name@domain.com" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
            </div>

            <!-- Address & Phone -->
            <div class="form-row">
                <div class="form-group">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="E.g., Koteshwor, Kathmandu" value="<?php echo htmlspecialchars($address); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number (Nepal)</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="98XXXXXXXX / 01XXXXXXX" value="<?php echo htmlspecialchars($phone); ?>" required>
                    <div class="validation-hint" id="phone-hint">Nepal mobile or landline formats only</div>
                </div>
            </div>

            <!-- Profile Photo Upload -->
            <div class="form-group">
                <label class="form-label" data-i18n="form.photo">Profile Photo</label>
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <div style="width:80px;height:80px;border-radius:50%;background:var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;border:2px solid var(--border);">
                        <img id="profilePhotoPreview" src="" alt="Preview" style="width:100%;height:100%;object-fit:cover;display:none;">
                        <i class="fa-solid fa-camera" style="font-size:24px;color:var(--text-muted);"></i>
                    </div>
                    <div>
                        <input type="file" id="profile_photo" name="profile_photo" class="form-control" accept="image/*" onchange="previewProfilePhoto(this)" style="padding:8px;">
                        <div class="validation-hint">Optional. JPG, PNG or WEBP. Max 2MB.</div>
                    </div>
                </div>
            </div>

            <!-- Password Fields -->
            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min. 8 characters" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>
            </div>

            <!-- Interactive Password Strength Indicators -->
            <div class="form-group">
                <label class="form-label" style="margin-bottom: 2px;">Password Strength Criteria:</label>
                <ul class="strength-checklist" id="strengthChecklist">
                    <li class="strength-item" data-rule="length"><i class="fa-regular fa-circle"></i> At least 8 characters</li>
                    <li class="strength-item" data-rule="uppercase"><i class="fa-regular fa-circle"></i> One uppercase letter</li>
                    <li class="strength-item" data-rule="lowercase"><i class="fa-regular fa-circle"></i> One lowercase letter</li>
                    <li class="strength-item" data-rule="number"><i class="fa-regular fa-circle"></i> One number</li>
                    <li class="strength-item" data-rule="special"><i class="fa-regular fa-circle"></i> One special character</li>
                    <li class="strength-item" data-rule="match"><i class="fa-regular fa-circle"></i> Passwords match</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                <i class="fa-solid fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Log In</a>
        </div>
        <div class="auth-footer" style="margin-top: 12px; font-size: 13px; color: var(--text-secondary);">
            <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Website</a>
        </div>
    </div>

    <!-- JavaScript Interactive Validations -->
    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const phoneInput = document.getElementById('phone');
        const phoneHint = document.getElementById('phone-hint');
        
        // Checklist Rules
        const rules = {
            length: (p) => p.length >= 8,
            uppercase: (p) => /[A-Z]/.test(p),
            lowercase: (p) => /[a-z]/.test(p),
            number: (p) => /\d/.test(p),
            special: (p) => /[@$!%*?&#]/.test(p),
            match: (p, c) => p && p === c
        };

        function updateChecklist() {
            const p = passwordInput.value;
            const c = confirmInput.value;

            for (const key in rules) {
                const item = document.querySelector(`.strength-item[data-rule="${key}"]`);
                const icon = item.querySelector('i');
                const isValid = rules[key](p, c);

                if (isValid) {
                    item.classList.add('valid');
                    item.classList.remove('invalid');
                    icon.className = 'fa-solid fa-circle-check';
                } else {
                    item.classList.remove('valid');
                    if (p.length > 0 || (key === 'match' && c.length > 0)) {
                        item.classList.add('invalid');
                        icon.className = 'fa-solid fa-circle-xmark';
                    } else {
                        item.classList.remove('invalid');
                        icon.className = 'fa-regular fa-circle';
                    }
                }
            }
        }

        passwordInput.addEventListener('input', updateChecklist);
        confirmInput.addEventListener('input', updateChecklist);

        // Nepal Phone Live Validation
        phoneInput.addEventListener('input', function() {
            const val = phoneInput.value.trim();
            const mobilePattern = /^(98|97|96)\d{8}$/;
            const landlinePattern = /^01\d{7}$/;
            
            if (val.length === 0) {
                phoneHint.style.color = '';
                phoneHint.textContent = 'Nepal mobile or landline formats only';
            } else if (mobilePattern.test(val) || landlinePattern.test(val)) {
                phoneHint.style.color = '#10b981';
                phoneHint.innerHTML = '<i class="fa-solid fa-circle-check"></i> Valid Nepalese Phone Number';
            } else {
                phoneHint.style.color = '#ef4444';
                phoneHint.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Invalid. Use 98/97/96 (10 digits) or 01 (9 digits)';
            }
        });
    </script>
</body>
</html>
