<?php
require_once __DIR__ . '/includes/init.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$page_title = 'Login';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!csrf_ok()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        $user = q_one($conn, 'SELECT user_id, full_name, email, password, role FROM users WHERE email = ?', 's', [$email]);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect('dashboard.php');
        }
        // Same message for unknown email and wrong password
        $error = 'Incorrect email or password.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <div class="form-card">
        <h1>Welcome back</h1>
        <p class="muted">Log in to manage your profile and trainings.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" id="loginForm" autocomplete="off" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="off" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" autocomplete="off" required>
                    <button type="button" class="password-toggle" aria-controls="password" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Log in</button>
        </form>

        <p class="form-foot"><a href="forgot_password.php"><strong>Forgot your password?</strong></a></p>
        <p class="form-foot">New here? <a href="register.php"><strong>Create an account</strong></a></p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
