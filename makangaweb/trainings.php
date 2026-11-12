<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Technical Skills Trainings';

// Handle enrolment (clients must be logged in)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enrol') {
    require_login();
    if (!csrf_ok()) {
        flash('error', 'Your session expired. Please try again.');
        redirect('trainings.php');
    }
    $training_id = (int)($_POST['training_id'] ?? 0);
    $exists = q_val($conn, 'SELECT COUNT(*) FROM trainings WHERE training_id = ?', 'i', [$training_id]);
    if (!$exists) {
        flash('error', 'That training was not found.');
    } else {
        $added = q_exec($conn, 'INSERT IGNORE INTO enrolments (user_id, training_id) VALUES (?, ?)', 'ii', [$_SESSION['user_id'], $training_id]);
        if ($added > 0) {
            flash('success', 'You are enrolled! We will contact you with the details.');
        } else {
            flash('info', 'You are already enrolled in that training.');
        }
    }
    redirect('trainings.php');
}

$trainings = q_all($conn, 'SELECT * FROM trainings ORDER BY start_date ASC, training_id ASC');

$mine = [];
if (is_logged_in()) {
    foreach (q_all($conn, 'SELECT training_id FROM enrolments WHERE user_id = ?', 'i', [$_SESSION['user_id']]) as $r) {
        $mine[(int)$r['training_id']] = true;
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="eyebrow">Learn with us</span>
        <h1>Practical skills trainings</h1>
        <p>Project-based training in web development, mechanical CAD and electronics design, taught in Soroti City.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!is_logged_in()): ?>
            <div class="alert alert-info">Want to join a course? <a href="login.php"><strong>Log in</strong></a> or <a href="register.php"><strong>create an account</strong></a> to enrol.</div>
        <?php endif; ?>

        <?php if ($trainings): ?>
            <div class="grid-2">
                <?php foreach ($trainings as $t): ?>
                    <article class="card">
                        <h3><?= e($t['title']) ?></h3>
                        <p class="muted grow"><?= e($t['description']) ?></p>
                        <div class="tags">
                            <span class="tag">Duration: <?= e($t['duration']) ?></span>
                            <span class="tag">Starts: <?= $t['start_date'] ? e(date('j M Y', strtotime($t['start_date']))) : 'To be announced' ?></span>
                        </div>
                        <div class="card-footer">
                            <strong style="font-family:var(--font-head);font-size:1.15rem"><?= $t['fee'] === null ? 'Fee to be confirmed' : e(money($t['fee'])) ?></strong>
                            <?php if (isset($mine[(int)$t['training_id']])): ?>
                                <span class="badge badge-ok">Enrolled</span>
                            <?php elseif (is_logged_in()): ?>
                                <form method="post" action="trainings.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="enrol">
                                    <input type="hidden" name="training_id" value="<?= (int)$t['training_id'] ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">Enrol now</button>
                                </form>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline btn-sm">Log in to enrol</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty">New trainings will be announced soon.</p>
        <?php endif; ?>

        <div class="card" style="margin-top:2.5rem">
            <h3>Custom training for your team or school</h3>
            <p class="muted" style="margin:0">Need a course for a class, youth group or company? <a href="contact.php">Contact us</a> and we will build a programme around your goals.</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
