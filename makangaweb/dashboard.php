<?php
require_once __DIR__ . '/includes/init.php';
require_login();
$page_title = 'Dashboard';
$uid = (int)$_SESSION['user_id'];
$first_name = preg_split('/\s+/', trim((string)($_SESSION['full_name'] ?? '')), 2)[0] ?? '';
$local_hour = (int)(new DateTimeImmutable('now', new DateTimeZone('Africa/Kampala')))->format('G');
$greeting = $local_hour < 12 ? 'Good morning' : ($local_hour < 17 ? 'Good afternoon' : 'Good evening');

/* =========================================================
   Client dashboard: personal enrolments only
   ========================================================= */
if (!is_admin()) {
    $my_enrolments = q_val($conn, 'SELECT COUNT(*) FROM enrolments WHERE user_id = ?', 'i', [$uid]);
    $open_trainings = q_val($conn, 'SELECT COUNT(*) FROM trainings');
    $enrolments = q_all(
        $conn,
        'SELECT t.title, t.duration, t.start_date, t.fee, e.enrol_date
         FROM enrolments e
         JOIN trainings t ON t.training_id = e.training_id
         WHERE e.user_id = ?
         ORDER BY e.enrol_date DESC',
        'i',
        [$uid]
    );
    include __DIR__ . '/includes/header.php';
    ?>
    <section class="page-banner">
        <div class="container">
            <span class="eyebrow">Your Acrobatic Tech Hub account</span>
            <h1><?= e($greeting) ?>, <?= e($first_name) ?>!</h1>
            <p>Welcome back. Here’s your training activity and a quick look at what’s available.</p>
        </div>
    </section>
    <div class="container" style="margin:2.5rem auto">
        <div class="grid-kpi">
            <div class="kpi"><b><?= (int)$my_enrolments ?></b><span>My enrolments</span></div>
            <div class="kpi"><b><?= (int)$open_trainings ?></b><span>Trainings available</span></div>
        </div>
        <section class="dash-section">
            <h2>My training enrolments</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Training</th><th>Duration</th><th>Start date</th><th>Fee</th><th>Enrolled</th></tr></thead>
                    <tbody>
                    <?php foreach ($enrolments as $enrolment): ?>
                        <tr>
                            <td><strong><?= e($enrolment['title']) ?></strong></td>
                            <td><?= e($enrolment['duration'] ?: 'Not specified') ?></td>
                            <td><?= $enrolment['start_date'] ? e(date('j M Y', strtotime($enrolment['start_date']))) : 'To be announced' ?></td>
                            <td><?= $enrolment['fee'] === null ? 'Fee to be confirmed' : e(money($enrolment['fee'])) ?></td>
                            <td><?= e(date('j M Y', strtotime($enrolment['enrol_date']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$enrolments): ?><tr><td colspan="5" class="empty">You have not enrolled in a training yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <p>
            <a href="profile.php" class="btn btn-outline">Go to my profile</a>
            <a href="trainings.php" class="btn btn-outline">Browse trainings</a>
        </p>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

/* =========================================================
   Admin actions
   ========================================================= */
function valid_date($d)
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash('error', 'Your session expired. Please try again.');
        redirect('dashboard.php');
    }
    $action = $_POST['action'] ?? '';
    $anchor = '';

    switch ($action) {
        case 'delete_user':
            $anchor = '#users';
            $id = (int)($_POST['user_id'] ?? 0);
            if ($id === $uid) {
                flash('error', 'You cannot delete your own account.');
            } elseif (q_exec($conn, 'DELETE FROM users WHERE user_id = ?', 'i', [$id]) > 0) {
                flash('success', 'User deleted.');
            } else {
                flash('error', 'User not found.');
            }
            break;

        case 'set_role':
            $anchor = '#users';
            $id = (int)($_POST['user_id'] ?? 0);
            $role = $_POST['role'] ?? '';
            if ($id === $uid) {
                flash('error', 'You cannot change your own role.');
            } elseif (!in_array($role, ['admin', 'client'], true)) {
                flash('error', 'Invalid role.');
            } else {
                q_exec($conn, 'UPDATE users SET role = ? WHERE user_id = ?', 'si', [$role, $id]);
                flash('success', 'User role updated.');
            }
            break;

        case 'add_project':
            $anchor = '#projects';
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $tech = trim($_POST['technologies'] ?? '');
            $client = trim($_POST['client_name'] ?? '');
            $status = $_POST['status'] ?? 'Completed';
            $date = trim($_POST['date_completed'] ?? '');
            if ($title === '' || $desc === '' || !in_array($status, ['Completed', 'Ongoing'], true) || ($date !== '' && !valid_date($date))) {
                flash('error', 'Please fill in the project title and description, and use a valid date.');
            } else {
                q_exec(
                    $conn,
                    'INSERT INTO projects (title, description, technologies, client_name, status, date_completed) VALUES (?, ?, ?, ?, ?, ?)',
                    'ssssss',
                    [mb_substr($title, 0, 150), $desc, mb_substr($tech, 0, 200), mb_substr($client, 0, 120), $status, $date === '' ? null : $date]
                );
                flash('success', 'Project added.');
            }
            break;

        case 'delete_project':
            $anchor = '#projects';
            q_exec($conn, 'DELETE FROM projects WHERE project_id = ?', 'i', [(int)($_POST['project_id'] ?? 0)]);
            flash('success', 'Project deleted.');
            break;

        case 'add_training':
            $anchor = '#trainings';
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $duration = trim($_POST['duration'] ?? '');
            $fee = trim($_POST['fee'] ?? '');
            $date = trim($_POST['start_date'] ?? '');
            if ($title === '' || ($fee !== '' && (!is_numeric($fee) || (float)$fee < 0)) || ($date !== '' && !valid_date($date))) {
                flash('error', 'Enter a training title, a valid fee if known, and a valid start date.');
            } else {
                $fee_value = $fee === '' ? null : $fee;
                q_exec(
                    $conn,
                    'INSERT INTO trainings (title, description, duration, fee, start_date) VALUES (?, ?, ?, ?, ?)',
                    'sssss',
                    [mb_substr($title, 0, 150), $desc, mb_substr($duration, 0, 50), $fee_value, $date === '' ? null : $date]
                );
                flash('success', 'Training added.');
            }
            break;

        case 'update_training_fee':
            $anchor = '#trainings';
            $id = filter_var($_POST['training_id'] ?? null, FILTER_VALIDATE_INT);
            $fee = trim($_POST['fee'] ?? '');
            if ($id === false || $id < 1 || ($fee !== '' && (!is_numeric($fee) || !is_finite((float)$fee) || (float)$fee < 0 || (float)$fee > 99999999.99))) {
                flash('error', 'Enter a valid training and fee, or leave the fee blank to be confirmed later.');
            } elseif (!q_val($conn, 'SELECT COUNT(*) FROM trainings WHERE training_id = ?', 'i', [$id])) {
                flash('error', 'Training not found.');
            } else {
                $fee_value = $fee === '' ? null : $fee;
                q_exec($conn, 'UPDATE trainings SET fee = ? WHERE training_id = ?', 'si', [$fee_value, $id]);
                flash('success', 'Training fee updated.');
            }
            break;

        case 'delete_training':
            $anchor = '#trainings';
            q_exec($conn, 'DELETE FROM trainings WHERE training_id = ?', 'i', [(int)($_POST['training_id'] ?? 0)]);
            flash('success', 'Training deleted.');
            break;

        case 'delete_message':
            $anchor = '#messages';
            q_exec($conn, 'DELETE FROM messages WHERE message_id = ?', 'i', [(int)($_POST['message_id'] ?? 0)]);
            flash('success', 'Message deleted.');
            break;

        case 'update_demo_request_status':
            $anchor = '#demo-requests';
            $id = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
            $status = $_POST['status'] ?? '';
            if ($id === false || $id < 1 || !in_array($status, ['Pending', 'Reviewed', 'Completed', 'Cancelled'], true)) {
                flash('error', 'Invalid sample request or status.');
            } elseif (q_exec($conn, 'UPDATE business_demo_requests SET status = ? WHERE request_id = ?', 'si', [$status, $id]) > 0) {
                flash('success', 'Sample request status updated.');
            } else {
                flash('error', 'Sample request not found or its status is unchanged.');
            }
            break;

        case 'delete_demo_request':
            $anchor = '#demo-requests';
            $id = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                flash('error', 'Invalid sample request.');
            } elseif (q_exec($conn, 'DELETE FROM business_demo_requests WHERE request_id = ?', 'i', [$id]) > 0) {
                flash('success', 'Sample request deleted.');
            } else {
                flash('error', 'Sample request not found.');
            }
            break;
    }
    redirect('dashboard.php' . $anchor);
}

/* =========================================================
   Admin data
   ========================================================= */
$kpi = [
    'Registered users' => q_val($conn, 'SELECT COUNT(*) FROM users'),
    'Clients'          => q_val($conn, "SELECT COUNT(*) FROM users WHERE role = 'client'"),
    'Admins'           => q_val($conn, "SELECT COUNT(*) FROM users WHERE role = 'admin'"),
    'Projects'         => q_val($conn, 'SELECT COUNT(*) FROM projects'),
    'Trainings'        => q_val($conn, 'SELECT COUNT(*) FROM trainings'),
    'Enrolments'       => q_val($conn, 'SELECT COUNT(*) FROM enrolments'),
    'Messages'         => q_val($conn, 'SELECT COUNT(*) FROM messages'),
    'Website requests' => q_val($conn, 'SELECT COUNT(*) FROM business_demo_requests'),
];

$users = q_all(
    $conn,
    'SELECT u.*, (SELECT COUNT(*) FROM enrolments e WHERE e.user_id = u.user_id) AS enrolled
     FROM users u ORDER BY u.created_at DESC, u.user_id DESC'
);
$projects = q_all($conn, 'SELECT * FROM projects ORDER BY project_id DESC');
$trainings = q_all(
    $conn,
    'SELECT t.*, (SELECT COUNT(*) FROM enrolments e WHERE e.training_id = t.training_id) AS learners
     FROM trainings t ORDER BY t.training_id DESC'
);
$messages = q_all($conn, 'SELECT * FROM messages ORDER BY sent_at DESC, message_id DESC');
$demoRequests = q_all(
    $conn,
    "SELECT r.*,
            GROUP_CONCAT(CONCAT(i.item_name, ' x ', i.quantity) ORDER BY i.request_item_id SEPARATOR ', ') AS items
     FROM business_demo_requests r
     LEFT JOIN business_demo_request_items i ON i.request_id = r.request_id
     GROUP BY r.request_id
     ORDER BY r.created_at DESC, r.request_id DESC
     LIMIT 100"
);

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="eyebrow">Admin section · Administrator</span>
        <h1><?= e($greeting) ?>, <?= e($first_name) ?>!</h1>
        <p>Manage users, projects, trainings, customer messages and website requests.</p>
    </div>
</section>

<nav class="dash-nav" aria-label="Dashboard sections">
    <div class="container">
        <a href="#overview">Overview</a>
        <a href="#users">Registered users</a>
        <a href="#projects">Projects</a>
        <a href="#trainings">Trainings</a>
        <a href="#messages">Messages</a>
        <a href="#demo-requests">Website requests</a>
    </div>
</nav>

<div class="container">

    <!-- Overview -->
    <section class="dash-section" id="overview">
        <h2>Overview</h2>
        <div class="grid-kpi">
            <?php foreach ($kpi as $label => $value): ?>
                <div class="kpi"><b><?= (int)$value ?></b><span><?= e($label) ?></span></div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Registered users -->
    <section class="dash-section" id="users">
        <h2>Registered users</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Trainings</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['user_id'] ?></td>
                        <td><strong><?= e($u['full_name']) ?></strong></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($u['phone']) ?></td>
                        <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-admin' : 'badge-client' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                        <td><?= (int)$u['enrolled'] ?></td>
                        <td><?= e(date('j M Y', strtotime($u['created_at']))) ?></td>
                        <td>
                            <?php if ((int)$u['user_id'] === $uid): ?>
                                <small class="muted">You</small>
                            <?php else: ?>
                                <div class="actions">
                                    <form method="post" action="dashboard.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="set_role">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'client' : 'admin' ?>">
                                        <button type="submit" class="btn btn-sm btn-outline"><?= $u['role'] === 'admin' ? 'Make client' : 'Make admin' ?></button>
                                    </form>
                                    <form method="post" action="dashboard.php" data-confirm="Delete this user and their enrolments?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Projects -->
    <section class="dash-section" id="projects">
        <h2>Projects</h2>
        <div class="grid-2" style="align-items:start">
            <div class="form-card">
                <h3>Add a project</h3>
                <form method="post" action="dashboard.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_project">
                    <div class="form-group"><label for="p_title">Title</label><input type="text" id="p_title" name="title" required></div>
                    <div class="form-group"><label for="p_desc">Description</label><textarea id="p_desc" name="description" required style="min-height:90px"></textarea></div>
                    <div class="form-group"><label for="p_tech">Technologies</label><input type="text" id="p_tech" name="technologies" placeholder="HTML, CSS, PHP, MySQL"></div>
                    <div class="form-row">
                        <div class="form-group"><label for="p_client">Client</label><input type="text" id="p_client" name="client_name"></div>
                        <div class="form-group"><label for="p_status">Status</label>
                            <select id="p_status" name="status"><option>Completed</option><option>Ongoing</option></select>
                        </div>
                    </div>
                    <div class="form-group"><label for="p_date">Date completed</label><input type="date" id="p_date" name="date_completed"></div>
                    <button type="submit" class="btn btn-primary">Add project</button>
                </form>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Project</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($projects as $p): ?>
                        <tr>
                            <td><strong><?= e($p['title']) ?></strong><br><small class="muted"><?= e($p['client_name']) ?></small></td>
                            <td><span class="badge <?= $p['status'] === 'Completed' ? 'badge-ok' : 'badge-warn' ?>"><?= e($p['status']) ?></span></td>
                            <td>
                                <form method="post" action="dashboard.php" data-confirm="Delete this project?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_project">
                                    <input type="hidden" name="project_id" value="<?= (int)$p['project_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$projects): ?><tr><td colspan="3" class="empty">No projects yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Trainings -->
    <section class="dash-section" id="trainings">
        <h2>Trainings</h2>
        <div class="grid-2" style="align-items:start">
            <div class="form-card">
                <h3>Add a training</h3>
                <form method="post" action="dashboard.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_training">
                    <div class="form-group"><label for="t_title">Title</label><input type="text" id="t_title" name="title" required></div>
                    <div class="form-group"><label for="t_desc">Description</label><textarea id="t_desc" name="description" style="min-height:90px"></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label for="t_dur">Duration</label><input type="text" id="t_dur" name="duration" placeholder="4 weeks"></div>
                        <div class="form-group"><label for="t_fee">Fee (UGX)</label><input type="number" id="t_fee" name="fee" min="0" step="1000" placeholder="Leave blank to confirm later"></div>
                    </div>
                    <div class="form-group"><label for="t_start">Start date</label><input type="date" id="t_start" name="start_date"></div>
                    <button type="submit" class="btn btn-primary">Add training</button>
                </form>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Course</th><th>Fee (UGX)</th><th>Learners</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($trainings as $t): ?>
                        <tr>
                            <td><strong><?= e($t['title']) ?></strong><br><small class="muted"><?= e($t['duration']) ?></small></td>
                            <td>
                                <form method="post" action="dashboard.php#trainings" class="actions">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_training_fee">
                                    <input type="hidden" name="training_id" value="<?= (int)$t['training_id'] ?>">
                                    <label class="sr-only" for="training-fee-<?= (int)$t['training_id'] ?>">Fee for <?= e($t['title']) ?></label>
                                    <input type="number" id="training-fee-<?= (int)$t['training_id'] ?>" name="fee" min="0" max="99999999.99" step="0.01" value="<?= $t['fee'] === null ? '' : e($t['fee']) ?>" placeholder="To be confirmed" required>
                                    <button type="submit" class="btn btn-sm btn-outline">Save fee</button>
                                </form>
                            </td>
                            <td><?= (int)$t['learners'] ?></td>
                            <td>
                                <form method="post" action="dashboard.php" data-confirm="Delete this training and its enrolments?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_training">
                                    <input type="hidden" name="training_id" value="<?= (int)$t['training_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$trainings): ?><tr><td colspan="4" class="empty">No trainings yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Messages -->
    <section class="dash-section" id="messages" style="padding-bottom:3rem">
        <h2>Contact messages</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Sent</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($messages as $m): ?>
                    <tr>
                        <td><strong><?= e($m['name']) ?></strong><br><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></td>
                        <td><?= e($m['subject'] ?: '(no subject)') ?></td>
                        <td style="min-width:240px"><?= nl2br(e($m['message'])) ?></td>
                        <td><?= e(date('j M Y, H:i', strtotime($m['sent_at']))) ?></td>
                        <td>
                            <form method="post" action="dashboard.php" data-confirm="Delete this message?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_message">
                                <input type="hidden" name="message_id" value="<?= (int)$m['message_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$messages): ?><tr><td colspan="5" class="empty">No messages yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="dash-section" id="demo-requests" style="padding-bottom:3rem">
        <h2>Website requests</h2>
        <p class="muted">These are requests for fictional sample businesses, not confirmed bookings, appointments or orders. The latest 100 are shown.</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Request</th><th>Customer</th><th>Items</th><th>Dates</th><th>Estimate</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($demoRequests as $request): ?>
                    <tr>
                        <td>
                            <strong>#<?= (int)$request['request_id'] ?> · <?= e(ucfirst(str_replace('_', ' ', $request['request_type']))) ?></strong><br>
                            <small class="muted"><?= e($request['business_slug']) ?> · <?= e(date('j M Y, H:i', strtotime($request['created_at']))) ?></small>
                        </td>
                        <td>
                            <strong><?= e($request['customer_name']) ?></strong><br>
                            <a href="mailto:<?= e($request['customer_email']) ?>"><?= e($request['customer_email']) ?></a>
                            <?php if ($request['customer_phone']): ?><br><small><?= e($request['customer_phone']) ?></small><?php endif; ?>
                        </td>
                        <td style="min-width:180px"><?= e($request['items'] ?: 'No items') ?></td>
                        <td>
                            <?= e(date('j M Y', strtotime($request['requested_date']))) ?>
                            <?php if ($request['checkout_date']): ?><br><small class="muted">to <?= e(date('j M Y', strtotime($request['checkout_date']))) ?> (<?= (int)$request['stay_nights'] ?> nights)</small><?php endif; ?>
                        </td>
                        <td><?= e(money($request['estimated_total'])) ?></td>
                        <td><span class="badge <?= $request['status'] === 'Completed' ? 'badge-ok' : 'badge-warn' ?>"><?= e($request['status']) ?></span></td>
                        <td>
                            <div class="actions">
                                <form method="post" action="dashboard.php#demo-requests">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_demo_request_status">
                                    <input type="hidden" name="request_id" value="<?= (int)$request['request_id'] ?>">
                                    <select name="status" aria-label="Status for request #<?= (int)$request['request_id'] ?>">
                                        <?php foreach (['Pending', 'Reviewed', 'Completed', 'Cancelled'] as $status): ?>
                                            <option value="<?= e($status) ?>" <?= $request['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline">Update</button>
                                </form>
                                <form method="post" action="dashboard.php#demo-requests" data-confirm="Delete this website request and its items?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_demo_request">
                                    <input type="hidden" name="request_id" value="<?= (int)$request['request_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$demoRequests): ?><tr><td colspan="7" class="empty">No website requests yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
