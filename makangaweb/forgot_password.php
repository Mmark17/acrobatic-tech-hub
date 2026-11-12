<?php
require_once __DIR__ . '/includes/init.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$page_title = 'Forgot password';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$message = '';
$localResetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $message = 'Your session expired. Please try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $email = mb_substr($email, 0, 120);
        $message = 'If an account exists for that email, we will send a password reset link. If it does not arrive, please contact us for help.';

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = q_one($conn, 'SELECT user_id FROM users WHERE email = ?', 's', [$email]);

            if ($user) {
                $requestHost = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''));
                $isLocalHost = is_array($requestHost)
                    && in_array(strtolower($requestHost['host'] ?? ''), ['localhost', '127.0.0.1', '::1'], true);
                $recent = q_val(
                    $conn,
                    'SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)',
                    'i',
                    [(int)$user['user_id']]
                );

                if (!$recent || $isLocalHost) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $baseUrl = rtrim((string)getenv('APP_BASE_URL'), '/');
                    $fromAddress = (string)getenv('MAIL_FROM_ADDRESS');
                    $baseUrlParts = parse_url($baseUrl);
                    $validBaseUrl = is_array($baseUrlParts)
                        && filter_var($baseUrl, FILTER_VALIDATE_URL)
                        && in_array($baseUrlParts['scheme'] ?? '', ['http', 'https'], true)
                        && empty($baseUrlParts['query'])
                        && empty($baseUrlParts['fragment'])
                        && empty($baseUrlParts['user'])
                        && empty($baseUrlParts['pass']);
                    q_exec($conn, 'DELETE FROM password_resets WHERE user_id = ?', 'i', [(int)$user['user_id']]);
                    q_exec(
                        $conn,
                        'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))',
                        'is',
                        [(int)$user['user_id'], $tokenHash]
                    );

                    if ($validBaseUrl && filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
                        $resetUrl = $baseUrl . '/reset_password.php?token=' . rawurlencode($token);
                        $subject = 'Reset your Acrobatic Tech Hub password';
                        $body = "We received a request to reset the password for your Acrobatic Tech Hub account.\n\n"
                            . "Use this link within one hour to choose a new password:\n"
                            . $resetUrl
                            . "\n\nIf you did not request this, you can ignore this email.";
                        $headers = [
                            'From' => $fromAddress,
                            'Content-Type' => 'text/plain; charset=UTF-8',
                            'X-Mailer' => 'PHP/' . PHP_VERSION,
                        ];
                        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

                        if (!mail($email, $encodedSubject, $body, $headers)) {
                            error_log('Password reset email could not be sent.');
                            if ($isLocalHost) {
                                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $appPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
                                $localResetLink = $scheme . '://' . $requestHost['host']
                                    . (isset($requestHost['port']) ? ':' . $requestHost['port'] : '')
                                    . $appPath . '/reset_password.php?token=' . rawurlencode($token);
                            } else {
                                q_exec($conn, 'DELETE FROM password_resets WHERE user_id = ?', 'i', [(int)$user['user_id']]);
                            }
                        }
                    } else {
                        error_log('Password reset email is not configured. Set APP_BASE_URL and MAIL_FROM_ADDRESS.');
                        if ($isLocalHost) {
                            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                            $appPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
                            $localResetLink = $scheme . '://' . $requestHost['host']
                                . (isset($requestHost['port']) ? ':' . $requestHost['port'] : '')
                                . $appPath . '/reset_password.php?token=' . rawurlencode($token);
                        } else {
                            q_exec($conn, 'DELETE FROM password_resets WHERE user_id = ?', 'i', [(int)$user['user_id']]);
                        }
                    }
                }
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <div class="form-card">
        <h1>Reset your password</h1>
        <p class="muted">Enter the email address linked to your account and we will send a reset link.</p>

        <?php if ($message): ?>
            <div class="alert alert-info" role="status"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($localResetLink): ?>
            <div class="alert alert-info" role="status">
                Email delivery is not configured on this local XAMPP server. Use this link to continue:
                <a href="<?= e($localResetLink) ?>">Open password reset page</a>
            </div>
        <?php endif; ?>

        <form method="post" action="forgot_password.php">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" maxlength="120" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
        </form>

        <p class="form-foot"><a href="login.php"><strong>Back to login</strong></a></p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
