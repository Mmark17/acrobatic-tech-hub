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
    $training = q_one($conn, 'SELECT fee FROM trainings WHERE training_id = ?', 'i', [$training_id]);
    if (!$training) {
        flash('error', 'That training was not found.');
    } else {
        $depositInput = $_POST['deposit_amount'] ?? '';
        $paymentMethod = $_POST['payment_method'] ?? '';
        $referenceInput = $_POST['transaction_reference'] ?? '';
        $paymentMethods = ['Mobile Money', 'Bank Transfer', 'Cash'];
        $deposit = null;
        $reference = is_string($referenceInput) ? trim($referenceInput) : '';
        $paymentError = null;

        if (!is_string($depositInput) || !is_string($paymentMethod) || !is_string($referenceInput)) {
            $paymentError = 'Enter valid deposit details.';
        } elseif ($depositInput !== '') {
            if (!preg_match('/\A\d{1,8}(?:\.\d{1,2})?\z/', $depositInput) || (float)$depositInput <= 0) {
                $paymentError = 'Enter a valid deposit amount greater than zero.';
            } elseif ($training['fee'] !== null && (float)$depositInput > (float)$training['fee']) {
                $paymentError = 'The deposit cannot be greater than the course fee.';
            } elseif (!in_array($paymentMethod, $paymentMethods, true)) {
                $paymentError = 'Choose a payment method for your deposit.';
            } elseif (mb_strlen($reference) > 100) {
                $paymentError = 'The payment reference must be 100 characters or fewer.';
            } else {
                $deposit = (float)$depositInput;
            }
        } elseif ($paymentMethod !== '' || $reference !== '') {
            $paymentError = 'Enter a deposit amount or clear the payment details to enrol without a deposit.';
        }

        if ($paymentError !== null) {
            flash('error', $paymentError);
        } else {
            try {
                $conn->begin_transaction();
                $added = q_exec($conn, 'INSERT IGNORE INTO enrolments (user_id, training_id) VALUES (?, ?)', 'ii', [$_SESSION['user_id'], $training_id]);
                if ($added > 0 && $deposit !== null) {
                    $enrolmentId = (int)$conn->insert_id;
                    q_exec(
                        $conn,
                        'INSERT INTO payments (enrol_id, amount, payment_method, transaction_reference) VALUES (?, ?, ?, ?)',
                        'idss',
                        [$enrolmentId, $deposit, $paymentMethod, $reference !== '' ? $reference : null]
                    );
                }
                $conn->commit();

                if ($added > 0) {
                    flash('success', $deposit === null
                        ? 'You are enrolled! We will contact you with the details.'
                        : 'Your enrolment and deposit details were submitted for confirmation.');
                } else {
                    flash('info', 'You are already enrolled in that training.');
                }
            } catch (mysqli_sql_exception $ex) {
                $conn->rollback();
                error_log('Training enrolment could not be saved: ' . $ex->getMessage());
                flash('error', 'Your enrolment could not be saved. Please try again.');
            }
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
                                <form method="post" action="trainings.php" style="width:100%;margin-top:.7rem">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="enrol">
                                    <input type="hidden" name="training_id" value="<?= (int)$t['training_id'] ?>">
                                    <div class="form-group">
                                        <label for="deposit-<?= (int)$t['training_id'] ?>">Initial deposit in UGX (optional)</label>
                                        <input type="number" id="deposit-<?= (int)$t['training_id'] ?>" name="deposit_amount" min="0.01" max="<?= $t['fee'] === null ? '99999999.99' : e($t['fee']) ?>" step="0.01" placeholder="Leave blank to enrol without a deposit">
                                    </div>
                                    <div class="form-group">
                                        <label for="payment-method-<?= (int)$t['training_id'] ?>">Payment method</label>
                                        <select id="payment-method-<?= (int)$t['training_id'] ?>" name="payment_method">
                                            <option value="">Choose if paying a deposit</option>
                                            <option value="Mobile Money">Mobile Money</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cash">Cash</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="payment-reference-<?= (int)$t['training_id'] ?>">Transaction reference (optional)</label>
                                        <input type="text" id="payment-reference-<?= (int)$t['training_id'] ?>" name="transaction_reference" maxlength="100">
                                    </div>
                                    <p class="hint">Deposit details are saved as pending until staff verifies them. This form does not process a transfer.</p>
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
