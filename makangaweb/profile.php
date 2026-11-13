<?php
require_once __DIR__ . '/includes/init.php';
require_login();
$page_title = 'My Profile';
$uid = (int)$_SESSION['user_id'];

$errors = [];
$pw_errors = [];
$photo_errors = [];
$user = q_one($conn, 'SELECT * FROM users WHERE user_id = ?', 'i', [$uid]);
if (!$user) {
    // Account was deleted while logged in
    redirect('logout.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    q_exec($conn, 'UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND read_at IS NULL', 'i', [$uid]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!csrf_ok()) {
        flash('error', 'Your session expired. Please try again.');
        redirect('profile.php');
    }

    if ($action === 'update_profile') {
        $name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            $errors[] = 'Enter your full name (3 to 100 characters).';
        }
        if (!preg_match('/^[0-9+\s-]{9,15}$/', $phone)) {
            $errors[] = 'Enter a valid phone number.';
        } else {
            $phone = normalize_phone($phone);
        }
        if (phone_in_use($conn, $phone, $uid)) {
            $errors[] = 'That phone number is already registered. Use a different number.';
        }
        if (!$errors) {
            try {
                q_exec($conn, 'UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?', 'ssi', [$name, $phone, $uid]);
                $_SESSION['full_name'] = $name;
                flash('success', 'Your profile has been updated.');
                redirect('profile.php');
            } catch (mysqli_sql_exception $ex) {
                if ((int)$ex->getCode() === 1062) {
                    $errors[] = 'That phone number is already registered. Use a different number.';
                } else {
                    throw $ex;
                }
            }
        }
    } elseif ($action === 'update_photo') {
        $upload = $_FILES['profile_photo'] ?? null;

        if (!is_array($upload) || !isset($upload['error'], $upload['size'], $upload['tmp_name']) || $upload['error'] === UPLOAD_ERR_NO_FILE) {
            $photo_errors[] = 'Choose a photo to upload.';
        } elseif ($upload['error'] !== UPLOAD_ERR_OK) {
            $photo_errors[] = $upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE
                ? 'The photo is too large. Choose an image no larger than 2 MB.'
                : 'The photo could not be uploaded. Please try again.';
        } elseif ($upload['size'] > 2 * 1024 * 1024 || !is_uploaded_file($upload['tmp_name'])) {
            $photo_errors[] = 'Choose a valid image no larger than 2 MB.';
        } else {
            $image_info = @getimagesize($upload['tmp_name']);
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
            $allowed_images = [
                'image/jpeg' => ['extension' => 'jpg', 'type' => IMAGETYPE_JPEG],
                'image/png' => ['extension' => 'png', 'type' => IMAGETYPE_PNG],
                'image/webp' => ['extension' => 'webp', 'type' => IMAGETYPE_WEBP],
            ];

            if (
                !$image_info
                || !isset($allowed_images[$mime])
                || $image_info['mime'] !== $mime
                || $image_info[2] !== $allowed_images[$mime]['type']
                || $image_info[0] > 5000
                || $image_info[1] > 5000
                || $image_info[0] * $image_info[1] > 20000000
            ) {
                $photo_errors[] = 'Use a valid JPG, PNG, or WebP image with dimensions no larger than 5000 × 5000 pixels.';
            } else {
                $upload_dir = __DIR__ . '/uploads/profile';
                if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
                    $photo_errors[] = 'The photo folder is unavailable. Please contact the site administrator.';
                } else {
                    $photo_name = bin2hex(random_bytes(16)) . '.' . $allowed_images[$mime]['extension'];
                    $photo_path = $upload_dir . '/' . $photo_name;

                    if (!move_uploaded_file($upload['tmp_name'], $photo_path)) {
                        $photo_errors[] = 'The photo could not be saved. Please try again.';
                    } else {
                        try {
                            q_exec($conn, 'UPDATE users SET profile_photo = ? WHERE user_id = ?', 'si', [$photo_name, $uid]);
                            if (!empty($user['profile_photo']) && preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $user['profile_photo'])) {
                                $old_photo_path = $upload_dir . '/' . $user['profile_photo'];
                                if (is_file($old_photo_path)) {
                                    unlink($old_photo_path);
                                }
                            }
                            flash('success', 'Your profile photo has been updated.');
                            redirect('profile.php');
                        } catch (mysqli_sql_exception $ex) {
                            if (is_file($photo_path)) {
                                unlink($photo_path);
                            }
                            $photo_errors[] = 'Your photo could not be added to your profile. Please try again.';
                        }
                    }
                }
            }
        }
    } elseif ($action === 'remove_photo') {
        $photo_name = $user['profile_photo'] ?? null;
        q_exec($conn, 'UPDATE users SET profile_photo = NULL WHERE user_id = ?', 'i', [$uid]);
        if ($photo_name && preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $photo_name)) {
            $photo_path = __DIR__ . '/uploads/profile/' . $photo_name;
            if (is_file($photo_path)) {
                unlink($photo_path);
            }
        }
        flash('success', 'Your profile photo has been removed.');
        redirect('profile.php');
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $row = q_one($conn, 'SELECT password FROM users WHERE user_id = ?', 'i', [$uid]);

        if (!$row || !password_verify($current, $row['password'])) {
            $pw_errors[] = 'Your current password is incorrect.';
        }
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
            $pw_errors[] = 'New password must be at least 8 characters with letters and numbers.';
        }
        if ($new !== $confirm) {
            $pw_errors[] = 'New passwords do not match.';
        }
        if (!$pw_errors) {
            q_exec($conn, 'UPDATE users SET password = ? WHERE user_id = ?', 'si', [password_hash($new, PASSWORD_DEFAULT), $uid]);
            flash('success', 'Your password has been changed.');
            redirect('profile.php');
        }
    } elseif ($action === 'unenrol') {
        $eid = (int)($_POST['enrol_id'] ?? 0);
        q_exec($conn, 'DELETE FROM enrolments WHERE enrol_id = ? AND user_id = ?', 'ii', [$eid, $uid]);
        flash('success', 'You have been removed from that training.');
        redirect('profile.php');
    }
}

$enrolments = q_all(
    $conn,
    'SELECT e.enrol_id, e.enrol_date, t.title, t.duration, t.fee, t.start_date
     FROM enrolments e JOIN trainings t ON t.training_id = e.training_id
     WHERE e.user_id = ? ORDER BY e.enrol_date DESC',
    'i',
    [$uid]
);
$payments = q_all(
    $conn,
    'SELECT p.amount, p.payment_method, p.transaction_reference, p.status, p.created_at, t.title
     FROM payments p
     JOIN enrolments e ON e.enrol_id = p.enrol_id
     JOIN trainings t ON t.training_id = e.training_id
     WHERE e.user_id = ?
     ORDER BY p.created_at DESC',
    'i',
    [$uid]
);
$my_messages = q_all($conn, 'SELECT subject, message, sent_at FROM messages WHERE email = ? ORDER BY sent_at DESC LIMIT 5', 's', [$user['email']]);
$my_replies = q_all(
    $conn,
    'SELECT r.reply_id, r.reply_body, r.created_at, m.subject, m.message
     FROM notifications n
     JOIN message_replies r ON r.reply_id = n.reply_id
     JOIN messages m ON m.message_id = r.message_id
     WHERE n.user_id = ?
     ORDER BY r.created_at DESC
     LIMIT 20',
    'i',
    [$uid]
);

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="eyebrow"><?= is_admin() ? 'Administrator account' : 'Client account' ?></span>
        <h1>My profile</h1>
        <p>View and update your details, and manage your training enrolments.</p>
    </div>
</section>

<div class="container">
    <div class="profile-grid">

        <aside class="card profile-card">
            <?php if (!empty($user['profile_photo'])): ?>
                <img class="avatar avatar-photo" src="<?= e('uploads/profile/' . $user['profile_photo']) ?>" alt="<?= e($user['full_name']) ?> profile photo">
            <?php else: ?>
                <div class="avatar"><?= e(strtoupper(mb_substr($user['full_name'], 0, 1))) ?></div>
            <?php endif; ?>
            <h3 style="margin-bottom:.2rem"><?= e($user['full_name']) ?></h3>
            <span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-client' ?>"><?= e(ucfirst($user['role'])) ?></span>
            <section class="photo-controls">
                <h4>Profile photo</h4>
                <?php if ($photo_errors): ?>
                    <div class="alert alert-error"><ul><?php foreach ($photo_errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="post" action="profile.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_photo">
                    <label class="sr-only" for="profile_photo">Choose a profile photo</label>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/webp" required>
                    <p class="hint">JPG, PNG, or WebP. Maximum 2 MB.</p>
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Upload photo</button>
                </form>
                <?php if (!empty($user['profile_photo'])): ?>
                    <form method="post" action="profile.php" data-confirm="Remove your profile photo?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove_photo">
                        <button type="submit" class="btn btn-sm btn-outline btn-block">Remove photo</button>
                    </form>
                <?php endif; ?>
            </section>
            <dl>
                <dt>Email</dt><dd><?= e($user['email']) ?></dd>
                <dt>Phone</dt><dd><?= e($user['phone'] ?: 'Not provided') ?></dd>
                <dt>Member since</dt><dd><?= e(date('j F Y', strtotime($user['created_at']))) ?></dd>
            </dl>
            <a href="dashboard.php" class="btn btn-dark btn-block" style="margin-top:1.2rem"><?= is_admin() ? 'Open admin dashboard' : 'Open my dashboard' ?></a>
            <a href="logout.php" class="btn btn-outline btn-block" style="margin-top:.7rem">Log out</a>
        </aside>

        <div class="stack">

            <section class="form-card">
                <h2 style="font-size:1.4rem">Edit details</h2>
                <?php if ($errors): ?>
                    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="post" action="profile.php" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full name</label>
                            <input type="text" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? $user['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?= e($_POST['phone'] ?? $user['phone']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email_ro">Email (cannot be changed)</label>
                        <input type="email" id="email_ro" value="<?= e($user['email']) ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </section>

            <section>
                <h2 style="font-size:1.4rem">My trainings</h2>
                <?php if ($enrolments): ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Course</th><th>Duration</th><th>Starts</th><th>Fee</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($enrolments as $en): ?>
                                <tr>
                                    <td><strong><?= e($en['title']) ?></strong><br><small class="muted">Enrolled <?= e(date('j M Y', strtotime($en['enrol_date']))) ?></small></td>
                                    <td><?= e($en['duration']) ?></td>
                                    <td><?= $en['start_date'] ? e(date('j M Y', strtotime($en['start_date']))) : 'TBA' ?></td>
                                    <td><?= $en['fee'] === null ? 'To be confirmed' : e(money($en['fee'])) ?></td>
                                    <td>
                                        <form method="post" action="profile.php" data-confirm="Leave this training?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="unenrol">
                                            <input type="hidden" name="enrol_id" value="<?= (int)$en['enrol_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline">Leave</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="card empty">You have not enrolled in any training yet. <a href="trainings.php"><strong>Browse trainings</strong></a></div>
                <?php endif; ?>
            </section>

            <?php if ($payments): ?>
            <section>
                <h2 style="font-size:1.4rem">My course deposits</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Course</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th>Submitted</th></tr></thead>
                        <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><strong><?= e($payment['title']) ?></strong></td>
                                <td><?= e(money($payment['amount'])) ?></td>
                                <td><?= e($payment['payment_method']) ?></td>
                                <td><?= e($payment['transaction_reference'] ?: 'Not provided') ?></td>
                                <td><?= e($payment['status']) ?></td>
                                <td><?= e(date('j M Y', strtotime($payment['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($my_messages): ?>
            <section>
                <h2 style="font-size:1.4rem">My recent messages</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Subject</th><th>Message</th><th>Sent</th></tr></thead>
                        <tbody>
                        <?php foreach ($my_messages as $m): ?>
                            <tr>
                                <td><?= e($m['subject'] ?: '(no subject)') ?></td>
                                <td><?= e(mb_strimwidth($m['message'], 0, 90, '...')) ?></td>
                                <td><?= e(date('j M Y', strtotime($m['sent_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($my_replies): ?>
            <section id="replies">
                <h2 style="font-size:1.4rem">Replies to your messages</h2>
                <div class="stack">
                    <?php foreach ($my_replies as $reply): ?>
                        <article class="card" id="reply-<?= (int)$reply['reply_id'] ?>">
                            <p><strong>Regarding: <?= e($reply['subject'] ?: '(no subject)') ?></strong></p>
                            <p class="muted"><?= nl2br(e($reply['message'])) ?></p>
                            <div class="alert alert-success"><?= nl2br(e($reply['reply_body'])) ?></div>
                            <small class="muted">Reply received <?= e(date('j M Y, H:i', strtotime($reply['created_at']))) ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="form-card">
                <h2 style="font-size:1.4rem">Change password</h2>
                <?php if ($pw_errors): ?>
                    <div class="alert alert-error"><ul><?php foreach ($pw_errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="post" action="profile.php" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label for="current_password">Current password</label>
                        <div class="password-input">
                            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" aria-controls="current_password" aria-label="Show password" aria-pressed="false">Show</button>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New password</label>
                            <div class="password-input">
                                <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>
                                <button type="button" class="password-toggle" aria-controls="new_password" aria-label="Show password" aria-pressed="false">Show</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm new password</label>
                            <div class="password-input">
                                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                                <button type="button" class="password-toggle" aria-controls="confirm_password" aria-label="Show password" aria-pressed="false">Show</button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark">Update password</button>
                </form>
            </section>

        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
