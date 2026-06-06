<?php
require_once 'config.php';

$errors = [];
$admin_key = '';
$flash = get_flash_message();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_key = trim($_POST['admin_key'] ?? '');

    if (empty($admin_key)) {
        $errors[] = 'Admin access key is required.';
    }

    if (empty($errors)) {
        $admin = verify_admin_key($pdo, $admin_key);
        if ($admin) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_name'] = $admin['name'];
            redirect('admin.php');
        } else {
            $errors[] = 'Invalid admin access key.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Sayog</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card animate-fade-in">
        <div class="auth-header">
            <a href="index.php" class="auth-logo">
                <div class="auth-logo-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <span>Sayog Admin</span>
            </a>
            <p class="auth-subtitle">Admin portal for managing users, products, and CMS content.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo $flash['message']; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div>
                    <i class="fa-solid fa-circle-xmark"></i>
                    <strong>Error:</strong>
                    <ul style="margin-top: 5px; padding-left: 20px; font-size: 13px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="admin-login.php" method="POST" novalidate>
            <div class="form-group">
                <label for="admin_key" class="form-label">Admin Access Key</label>
                <input type="password" id="admin_key" name="admin_key" class="form-control" placeholder="Enter admin access key" value="<?php echo htmlspecialchars($admin_key); ?>" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In to Admin
            </button>
        </form>

        <div class="auth-footer">
            Back to <a href="login.php">User Login</a>
        </div>
    </div>
</body>
</html>
