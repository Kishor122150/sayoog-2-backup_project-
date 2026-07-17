<?php
require_once 'config.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Check if we have registration data in session
$reg_data = $_SESSION['reg_data'] ?? null;
if (!$reg_data) {
    set_flash_message('warning', 'No pending registration found. Please register first.');
    redirect('register.php');
}

$email = $reg_data['email'];
$name = $reg_data['name'];
$errors = [];
$success = '';

// Clean up expired OTPs
cleanup_expired_otps($pdo);

// ── Development mode: Check if SMTP is active ──
$smtp_active = false;
try {
    $stmt = $pdo->prepare("SELECT is_active, host FROM smtp_settings WHERE id = 1");
    $stmt->execute();
    $smtp_row = $stmt->fetch();
    $smtp_active = !empty($smtp_row['is_active']) && !empty($smtp_row['host']);
} catch (PDOException $e) {
    $smtp_active = false;
}

// Fetch the latest OTP for display in development mode
$dev_otp = null;
if (!$smtp_active) {
    $stmt = $pdo->prepare("SELECT otp, expires_at FROM email_verifications WHERE email = ? AND verified = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $dev_otp_row = $stmt->fetch();
    if ($dev_otp_row && strtotime($dev_otp_row['expires_at']) > time()) {
        $dev_otp = $dev_otp_row['otp'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'verify';

    if ($action === 'verify') {
        $otp = preg_replace('/[^0-9]/', '', $_POST['otp'] ?? '');

        if (empty($otp) || strlen($otp) !== 6) {
            $errors[] = "Please enter a valid 6-digit OTP.";
        } else {
            $otp_result = verify_otp($pdo, $email, $otp);
            
            if ($otp_result === 'locked') {
                $errors[] = "Too many failed attempts. This OTP has been locked. Please request a new OTP.";
            } elseif ($otp_result === 'expired') {
                $errors[] = "Your OTP has expired or is invalid. Please request a new OTP.";
            } elseif ($otp_result === true) {
                // OTP is valid — create the user account
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, address, phone, password, role, profile_photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $reg_data['name'],
                        $reg_data['email'],
                        $reg_data['address'],
                        $reg_data['phone'],
                        $reg_data['password_hash'],
                        'user',
                        $reg_data['profile_photo']
                    ]);

                    // Send welcome notification
                    $new_user_id = $pdo->lastInsertId();
                    create_notification($pdo, $new_user_id, 'registration',
                        'Welcome to Sayog, ' . $reg_data['name'] . '! Your email has been verified and your account created successfully.',
                        'login.php', true);

                    // Clear registration data
                    unset($_SESSION['reg_data']);

                    set_flash_message('success', 'Email verified! Your account has been created successfully. You can now log in.');
                    redirect('login.php');
                } catch (PDOException $e) {
                    $errors[] = "Failed to create account: " . $e->getMessage();
                }                } else {
            $errors[] = "Invalid OTP. Please check the code and try again. (Max 5 attempts)";
        }
        }
    }

    if ($action === 'resend') {
        // Generate and send new OTP
        $otp = generate_otp();
        store_otp($pdo, $email, $otp);
        $emailSent = send_otp_email($pdo, $email, $otp, $name);

        if ($emailSent) {
            $success = 'A new OTP has been sent to ' . htmlspecialchars($email) . '. Please check your inbox.';
        } else {
            $success = 'Unable to send email. Please try the resend option again in a few moments.';
        }
    }
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | Sayog</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/app.js"></script>
    <style>
        .otp-container {
            text-align: center;
        }
        .otp-display {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin: 28px 0 20px;
        }
        .otp-input {
            width: 56px;
            height: 72px;
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            border: 2px solid var(--border);
            border-radius: 14px;
            background: var(--surface);
            color: var(--text-primary);
            transition: all 0.2s;
            outline: none;
            font-family: 'Inter', sans-serif;
        }
        .otp-input:focus {
            border-color: #059669;
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.12);
        }
        .otp-input.filled {
            border-color: #059669;
            background: rgba(5, 150, 105, 0.04);
        }
        .otp-email-display {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: rgba(5, 150, 105, 0.06);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #059669;
            margin-bottom: 4px;
        }
        .otp-timer {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 12px;
        }
        .otp-timer strong {
            color: var(--text-primary);
        }
        .resend-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #059669;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
            background: none;
            border: none;
            cursor: pointer;
        }
        .resend-link:hover {
            background: rgba(5, 150, 105, 0.06);
        }
        .hidden-otp-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
        }
    </style>
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
            <p class="auth-subtitle" style="margin-top:4px;">Verify your email address</p>
            <div style="display:flex;gap:8px;justify-content:center;margin-top:12px;">
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <button class="lang-toggle" onclick="toggleLanguage()" style="background:rgba(59,130,246,0.1);padding:6px 14px;border-radius:999px;border:1px solid var(--border);cursor:pointer;font-size:12px;font-weight:600;color:var(--text-secondary);">
                    <span>नेपाली</span>
                </button>
            </div>
        </div>

        <!-- Step indicator -->
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:20px;">
            <span style="width:32px;height:32px;border-radius:50%;background:#059669;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;"><i class="fa-solid fa-check"></i></span>
            <div style="width:40px;height:2px;background:#059669;border-radius:2px;"></div>
            <span style="width:32px;height:32px;border-radius:50%;background:#059669;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">2</span>
            <div style="width:40px;height:2px;background:var(--border);border-radius:2px;"></div>
            <span style="width:32px;height:32px;border-radius:50%;background:var(--border);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">3</span>
        </div>
        <div style="display:flex;justify-content:center;gap:70px;font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:24px;">
            <span style="color:#059669;">Register</span>
            <span style="color:#059669;">Verify</span>
            <span>Complete</span>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'info' ? 'fa-circle-info' : 'fa-circle-exclamation'); ?>"></i>
                <span><?php echo $flash['message']; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div>
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Error:</strong>
                    <ul style="margin-top: 5px; padding-left: 20px; font-size: 13px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo $err; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-info">
                <i class="fa-solid fa-circle-info"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <div class="otp-container">
            <p style="font-size:14px;color:var(--text-secondary);margin-bottom:16px;">
                Enter the 6-digit code sent to
            </p>
            <div class="otp-email-display">
                <i class="fa-solid fa-envelope"></i>
                <?php echo htmlspecialchars($email); ?>
            </div>

            <form action="verify-otp.php" method="POST" id="otpForm" novalidate>
                <input type="hidden" name="action" value="verify">
                
                <!-- Hidden combined OTP field for form submission -->
                <input type="hidden" name="otp" id="otpCombined" value="">

                <!-- 6 individual OTP digit boxes -->
                <div class="otp-display" id="otpBoxes">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" 
                               class="otp-input" 
                               id="otp_<?php echo $i; ?>" 
                               maxlength="1" 
                               inputmode="numeric" 
                               pattern="[0-9]"
                               autocomplete="off"
                               data-index="<?php echo $i; ?>"
                               required>
                    <?php endfor; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 8px;">
                    <i class="fa-solid fa-circle-check"></i> Verify Email
                </button>
            </form>

            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border);">
                <p class="otp-timer">
                    <i class="fa-regular fa-clock"></i> 
                    OTP expires in <strong id="otpTimer">05:00</strong>
                </p>
                <form action="verify-otp.php" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="resend-link">
                        <i class="fa-solid fa-rotate"></i> Resend OTP
                    </button>
                </form>
            </div>

            <?php if (!$smtp_active && $dev_otp): ?>
                <div style="margin-top: 24px; padding: 16px 20px; background: #fffbeb; border: 2px dashed #f59e0b; border-radius: 12px; text-align: center;">
                    <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:8px;">
                        <span style="background:#f59e0b;color:#fff;font-size:11px;font-weight:700;padding:2px 10px;border-radius:999px;text-transform:uppercase;letter-spacing:1px;">DEV MODE</span>
                        <span style="font-size:13px;color:#92400e;">SMTP not configured — showing OTP for testing</span>
                    </div>
                    <div style="font-size:42px;font-weight:800;letter-spacing:12px;color:#059669;font-family:monospace;"><?php echo htmlspecialchars($dev_otp); ?></div>
                    <p style="font-size:12px;color:#b45309;margin-top:8px;">
                        <i class="fa-solid fa-circle-info"></i>
                        Configure SMTP in the <a href="admin/admin.php?section=smtp" style="color:#059669;font-weight:600;">admin panel</a> to send real emails
                    </p>
                </div>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <a href="register.php" style="font-size:13px;color:var(--text-secondary);">
                    <i class="fa-solid fa-arrow-left"></i> Back to registration
                </a>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const inputs = document.querySelectorAll('.otp-input');
        const hiddenInput = document.getElementById('otpCombined');
        const form = document.getElementById('otpForm');

        // Focus first input on load
        if (inputs.length > 0) inputs[0].focus();

        // Handle digit input — auto-advance to next field
        inputs.forEach(function(input, index) {
            input.addEventListener('input', function(e) {
                const val = this.value.replace(/[^0-9]/g, '');
                this.value = val;
                
                if (val && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                
                // Update filled state
                this.classList.toggle('filled', val.length > 0);
                updateHiddenInput();
            });

            // Handle backspace — go to previous field
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    inputs[index - 1].focus();
                    inputs[index - 1].value = '';
                    inputs[index - 1].classList.remove('filled');
                    updateHiddenInput();
                }
                if (e.key === 'ArrowLeft' && index > 0) {
                    inputs[index - 1].focus();
                }
                if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            // Handle paste — fill all fields
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const digits = paste.replace(/[^0-9]/g, '').substring(0, 6);
                
                for (var i = 0; i < digits.length && i < inputs.length; i++) {
                    inputs[i].value = digits[i];
                    inputs[i].classList.toggle('filled', true);
                }
                if (digits.length < inputs.length) {
                    inputs[digits.length].focus();
                } else {
                    inputs[inputs.length - 1].focus();
                }
                updateHiddenInput();
            });
        });

        function updateHiddenInput() {
            var otp = '';
            inputs.forEach(function(inp) {
                otp += inp.value;
            });
            hiddenInput.value = otp;
        }

        // OTP Timer — 5 minutes countdown
        var timeLeft = 300; // 5 minutes in seconds
        var timerDisplay = document.getElementById('otpTimer');

        function updateTimer() {
            if (!timerDisplay) return;
            var mins = Math.floor(timeLeft / 60);
            var secs = timeLeft % 60;
            timerDisplay.textContent = 
                String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            
            if (timeLeft <= 60) {
                timerDisplay.style.color = '#ef4444';
            }
            
            if (timeLeft <= 0) {
                timerDisplay.textContent = 'Expired';
                timerDisplay.style.color = '#ef4444';
                return;
            }
            timeLeft--;
            setTimeout(updateTimer, 1000);
        }
        updateTimer();
    })();
    </script>
</body>
</html>
