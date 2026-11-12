<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Register';

if (is_logged_in()) {
    redirect('profile.php');
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email'] = strtolower(trim($_POST['email'] ?? ''));
    $old['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!csrf_ok()) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if (mb_strlen($old['full_name']) < 3 || mb_strlen($old['full_name']) > 100) {
        $errors[] = 'Enter your full name (3 to 100 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 120) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!preg_match('/^[0-9+\s-]{9,15}$/', $old['phone'])) {
        $errors[] = 'Enter a valid phone number.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must be at least 8 characters and include letters and numbers.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Duplicate email check
    if (!$errors) {
        $taken = q_val($conn, 'SELECT COUNT(*) FROM users WHERE email = ?', 's', [$old['email']]);
        if ($taken) {
            $errors[] = 'An account with that email already exists. Try logging in.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            q_exec(
                $conn,
                "INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'client')",
                'ssss',
                [$old['full_name'], $old['email'], $old['phone'], $hash]
            );
            flash('success', 'Account created! You can now log in.');
            redirect('login.php');
        } catch (mysqli_sql_exception $ex) {
            $errors[] = 'We could not create your account. The email may already be registered.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <div class="form-card">
        <h1>Create your account</h1>
        <p class="muted">Register to enrol in trainings and manage your profile.</p>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                Please fix the following:
                <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php" id="registerForm" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" autocomplete="name" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" placeholder="+256 7XX XXX XXX" autocomplete="tel" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" aria-controls="password" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
                <div class="strength"><i id="strengthBar"></i></div>
                <span class="hint">At least 8 characters, with letters and numbers.</span>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm password</label>
                <div class="password-input">
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" aria-controls="confirm_password" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create account</button>
        </form>

        <p class="form-foot">Already registered? <a href="login.php"><strong>Log in</strong></a></p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
