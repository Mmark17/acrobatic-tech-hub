<?php
require_once __DIR__ . '/includes/init.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
$page_title = 'Choose a new password';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$tokenValidFormat = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$reset = $tokenValidFormat
    ? q_one(
        $conn,
        'SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()',
        's',
        [hash('sha256', $token)]
    )
    : null;
$errors = [];
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!csrf_ok()) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if (!$reset) {
        $errors[] = 'This reset link is invalid or has expired. Request a new one.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must be at least 8 characters and include letters and numbers.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->begin_transaction();
        try {
            $claimed = q_exec(
                $conn,
                'DELETE FROM password_resets WHERE token_hash = ? AND expires_at > NOW()',
                's',
                [hash('sha256', $token)]
            );

            if (!$claimed) {
                $conn->rollback();
                $errors[] = 'This reset link is invalid or has expired. Request a new one.';
                $reset = null;
            } else {
                q_exec($conn, 'UPDATE users SET password = ? WHERE user_id = ?', 'si', [$hash, (int)$reset['user_id']]);
                q_exec($conn, 'DELETE FROM password_resets WHERE user_id = ?', 'i', [(int)$reset['user_id']]);
                $conn->commit();
                flash('success', 'Your password has been updated. You can now log in.');
                redirect('login.php');
            }
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            throw $ex;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <div class="form-card">
        <h1>Choose a new password</h1>
        <?php if (!$reset): ?>
            <div class="alert alert-error" role="alert">This reset link is invalid or has expired. Request a new one.</div>
            <p class="form-foot"><a href="forgot_password.php"><strong>Request a new reset link</strong></a></p>
        <?php else: ?>
            <p class="muted">Choose a new password for your account.</p>

            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert">
                    Please fix the following:
                    <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" action="reset_password.php">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="form-group">
                    <label for="password">New password</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" required>
                    <span class="hint">At least 8 characters, with letters and numbers.</span>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm new password</label>
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Update password</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
