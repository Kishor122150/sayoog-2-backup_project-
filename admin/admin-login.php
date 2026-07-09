<?php
require_once '../config.php';

$errors = [];
$email = '';
$flash = get_flash_message();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = 'Admin email is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_name'] = $admin['name'];
            redirect('admin.php');
        } else {
            $errors[] = 'Invalid admin email or password.';
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="admin-login-wrapper">
    <div class="admin-login-card">
        <div class="admin-login-header">
            <a href="../index.php" class="admin-login-logo">
                <div class="admin-login-logo-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <span>Sayog</span>
            </a>
            <h1>Admin Portal</h1>
            <p>Sign in to manage users, donations, and content</p>
        </div>

        <?php if ($flash): ?>
            <div class="admin-alert admin-alert-<?php echo $flash['type']; ?>">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo $flash['message']; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="admin-alert admin-alert-danger">
                <i class="fa-solid fa-circle-xmark"></i>
                <div>
                    <strong>Error:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="admin-login.php" method="POST" novalidate>
            <div class="form-group">
                <label for="email"><i class="fa-regular fa-envelope"></i> Admin Email</label>
                <input type="text" id="email" name="email" class="form-control" placeholder="admin@123" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter admin password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 6px;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In to Admin
            </button>
        </form>

        <div class="admin-login-footer">
            <i class="fa-regular fa-arrow-left"></i> Back to <a href="../login.php">User Login</a>
        </div>
    </div>
</body>
</html>
