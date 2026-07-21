<?php
require_once 'config.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$email = '';
$flash = get_flash_message();
$redirect = sanitize($_GET['redirect'] ?? $_POST['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = sanitize($_POST['redirect'] ?? '');

    if (empty($email)) {
        $errors[] = "Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (($user['role'] ?? 'user') === 'admin') {
                    $errors[] = 'Admin users must sign in through the admin portal.';
                } else {
                    // Login successful, set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_address'] = $user['address'];
                    $_SESSION['user_phone'] = $user['phone'];
                    $_SESSION['user_role'] = $user['role'] ?? 'user';
                    $_SESSION['user_photo'] = $user['profile_photo'] ?? null;
                    if (!empty($redirect) && strpos($redirect, 'http') !== 0 && strpos($redirect, '//') !== 0) {
                        redirect($redirect);
                    }
                    redirect('dashboard.php');
                }
            } else {
                $errors[] = "Invalid email or password combination.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | Sayog - Food Donation System</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/premium.css">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/app.js"></script>
</head>
<body class="auth-wrapper">
    <div class="auth-card animate-fade-in">
        <div class="auth-header">
            <a href="/frontend/index.php" class="auth-logo">
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

        <!-- Display Success Flash Messages (e.g. Registered successfully) -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo $flash['message']; ?></span>
            </div>
        <?php endif; ?>

        <!-- Display Errors -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div>
                    <i class="fa-solid fa-circle-xmark"></i>
                    <strong>Error:</strong>
                    <ul style="margin-top: 5px; padding-left: 20px; font-size: 13px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" novalidate>
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
            <!-- Email -->
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@domain.com" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
            </div>

            <!-- Password -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="password" class="form-label" style="margin-bottom: 0;">Password</label>
                </div>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div style="text-align: center; margin: 15px 0; position: relative;">
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 0;">
            <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--surface); padding: 0 10px; font-size: 13px; color: var(--text-secondary);">or</span>
        </div>

        <!-- <a href="#" class="btn btn-block" style="background-color: white; color: #333; border: 1px solid #ddd; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
            <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo" width="20" height="20">
            Sign in with Google
        </a> -->

        <div class="auth-footer">
            Don't have an account? <a href="/register.php">Sign Up</a>
        </div>
        <div class="auth-footer" style="margin-top: 12px; font-size: 13px; color: var(--text-secondary);">
            <a href="/frontend/index.php"><i class="fa-solid fa-arrow-left"></i> Back to Website</a>
        </div>
        <div class="auth-footer" style="margin-top: 12px; font-size: 13px; color: var(--text-secondary);">
            Are you an administrator? <a href="/admin/admin-login.php">Sign in to Admin Panel</a>
        </div>
    </div>
</body>
</html>
