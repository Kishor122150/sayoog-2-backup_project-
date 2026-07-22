<?php
require_once '../config.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('../dashboard.php');
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

    $account_type = sanitize($_POST['account_type'] ?? 'personal');
    if (!in_array($account_type, ['personal', 'ngo', 'other'])) {
        $account_type = 'personal';
    }
    
    // NGO-specific validation
    $org_name = '';
    $org_registration = '';
    $org_type = '';
    $org_district = '';
    $org_ward = '';
    $org_certificate_path = null;
    
    if ($account_type === 'ngo') {
        $org_name = sanitize($_POST['org_name'] ?? '');
        $org_type = sanitize($_POST['org_type'] ?? 'ngo');
        $org_district = sanitize($_POST['org_district'] ?? '');
        $org_ward = sanitize($_POST['org_ward'] ?? '');
        $org_registration = sanitize($_POST['org_registration'] ?? '');
        
        if (empty($org_name)) $errors[] = "Organization name is required.";
        if (empty($org_district)) $errors[] = "District is required.";
        
        // Handle certificate upload
        if (!empty($_FILES['org_certificate']['name'])) {
            $certFile = $_FILES['org_certificate'];
            if ($certFile['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($certFile['tmp_name']);
                if (in_array($mime, $allowedTypes, true) && $certFile['size'] <= 5 * 1024 * 1024) {
                    $extension = pathinfo($certFile['name'], PATHINFO_EXTENSION);
                    $filename = 'ngo_cert_' . uniqid() . '.' . strtolower($extension);
                    $destination = UPLOADS_DIR . '/' . $filename;
                    if (move_uploaded_file($certFile['tmp_name'], $destination)) {
                        $org_certificate_path = 'uploads/' . $filename;
                    }
                }
            }
        }
    }

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

    // Register User — Step 1: Send OTP email first
    if (empty($errors)) {
        try {
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

            // Generate and send OTP
            $otp = generate_otp();
            store_otp($pdo, $email, $otp);
            $emailSent = send_otp_email($pdo, $email, $otp, $name);

            // Store registration data in session for the next step (hash password first)
            $_SESSION['reg_data'] = [
                'name' => $name,
                'email' => $email,
                'address' => $address,
                'phone' => $phone,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'profile_photo' => $profile_photo,
                'account_type' => $account_type,
                'org_name' => $org_name,
                'org_registration' => $org_registration,
                'org_certificate' => $org_certificate_path,
                'org_type' => $org_type,
                'org_district' => $org_district,
                'org_ward' => $org_ward,
            ];

            if ($emailSent) {
                set_flash_message('success', 'A 6-digit OTP has been sent to ' . htmlspecialchars($email) . '. Please check your inbox and enter the code to complete registration.');
            } else {
                set_flash_message('info', 'Unable to send email automatically. Please use the resend option on the verification page to try again.');
            }
            redirect('../verify-otp.php');
        } catch (PDOException $e) {
            $errors[] = "Failed to process registration: " . $e->getMessage();
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
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/premium.css">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/app.js"></script>
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <a href="index.php" class="auth-logo">
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

            <!-- Account Type Selection -->
            <div class="form-group">
                <label class="form-label">Account Type</label>
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:4px;">
                    <label style="display:flex;align-items:center;gap:8px;padding:10px 18px;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:all 0.2s;flex:1;min-width:120px;" id="accountTypePersonal" onclick="selectAccountType('personal')">
                        <input type="radio" name="account_type" value="personal" checked style="accent-color:#059669;">
                        <div>
                            <div style="font-weight:600;font-size:14px;"><i class="fa-solid fa-user"></i> Personal</div>
                            <div style="font-size:11px;color:var(--text-muted);">Donate & request as an individual</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;padding:10px 18px;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:all 0.2s;flex:1;min-width:120px;" id="accountTypeNgo" onclick="selectAccountType('ngo')">
                        <input type="radio" name="account_type" value="ngo" style="accent-color:#059669;">
                        <div>
                            <div style="font-weight:600;font-size:14px;"><i class="fa-solid fa-building-columns"></i> NGO / Organization</div>
                            <div style="font-size:11px;color:var(--text-muted);">Represent an organization</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;padding:10px 18px;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:all 0.2s;flex:1;min-width:120px;" id="accountTypeOther" onclick="selectAccountType('other')">
                        <input type="radio" name="account_type" value="other" style="accent-color:#059669;">
                        <div>
                            <div style="font-weight:600;font-size:14px;"><i class="fa-solid fa-users"></i> Others</div>
                            <div style="font-size:11px;color:var(--text-muted);">Community group, school, etc.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- NGO Details Section (hidden by default, shown when NGO selected) -->
            <div id="ngoSection" style="display:none;background:#f0fdf4;border:2px solid #a7f3d0;border-radius:14px;padding:20px;margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                    <div style="width:36px;height:36px;background:linear-gradient(135deg,#059669,#10b981);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-building-columns" style="color:#fff;font-size:16px;"></i>
                    </div>
                    <div>
                        <h3 style="margin:0;font-size:15px;font-weight:700;color:#0f172a;">Organization Details</h3>
                        <p style="margin:2px 0 0;font-size:12px;color:#4b5563;">Your details will be sent for verification after registration.</p>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="org_name" class="form-label">Organization Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="org_name" name="org_name" class="form-control" placeholder="E.g., Hamro Sahayog Foundation">
                    </div>
                    <div class="form-group">
                        <label for="org_type" class="form-label">Organization Type</label>
                        <select id="org_type" name="org_type" class="form-control">
                            <option value="ngo">NGO (Non-Governmental Organization)</option>
                            <option value="community_group">Community Group</option>
                            <option value="school">School / Educational Institution</option>
                            <option value="trust">Trust / Foundation</option>
                            <option value="business">Business / Corporate</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="org_registration" class="form-label">Registration Number</label>
                        <input type="text" id="org_registration" name="org_registration" class="form-control" placeholder="E.g., SWC-12345 (optional)">
                        <div class="validation-hint">Providing a registration number helps faster verification</div>
                    </div>
                    <div class="form-group">
                        <label for="org_district" class="form-label">District <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="org_district" name="org_district" class="form-control" placeholder="E.g., Kathmandu, Lalitpur">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="org_ward" class="form-label">Ward / Municipality</label>
                        <input type="text" id="org_ward" name="org_ward" class="form-control" placeholder="E.g., Ward No. 5">
                    </div>
                    <div class="form-group">
                        <label for="org_certificate" class="form-label">Registration Certificate</label>
                        <input type="file" id="org_certificate" name="org_certificate" class="form-control" accept=".jpg,.jpeg,.png,.pdf" style="padding:8px;">
                        <div class="validation-hint">Optional. Upload SWC or company registration certificate (PDF/Image, max 5MB)</div>
                    </div>
                </div>
                
                <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;font-size:12px;color:#92400e;margin-top:8px;">
                    <i class="fa-solid fa-circle-info"></i> 
                    Your organization details will be reviewed by the admin team. Once verified, your posts and requests will display 
                    your organization name with a verified badge. Registration details are visible only to you and the admin.
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
            Already have an account? <a href="/frontend/login.php">Log In</a>
        </div>
        <div class="auth-footer" style="margin-top: 12px; font-size: 13px; color: var(--text-secondary);">
            <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Website</a>
        </div>
    </div>

    <script>
        // Account Type Selection
        function selectAccountType(type) {
            document.querySelectorAll('input[name="account_type"]').forEach(function(r) {
                r.checked = (r.value === type);
            });
            var cards = ['personal', 'ngo', 'other'];
            cards.forEach(function(t) {
                var el = document.getElementById('accountType' + t.charAt(0).toUpperCase() + t.slice(1));
                if (el) {
                    el.style.borderColor = (t === type) ? '#059669' : 'var(--border)';
                    el.style.background = (t === type) ? 'rgba(5,150,105,0.06)' : '';
                }
            });
            var ngoSection = document.getElementById('ngoSection');
            if (ngoSection) {
                ngoSection.style.display = (type === 'ngo') ? 'block' : 'none';
                document.getElementById('org_name').required = (type === 'ngo');
                document.getElementById('org_district').required = (type === 'ngo');
            }
        }
        
        (function() {
            var selectedType = document.querySelector('input[name="account_type"]:checked');
            if (selectedType) selectAccountType(selectedType.value);
        })();
        
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const phoneInput = document.getElementById('phone');
        const phoneHint = document.getElementById('phone-hint');
        
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
