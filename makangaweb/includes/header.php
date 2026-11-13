<?php
$page_title = $page_title ?? 'Acrobatic Tech Hub';
$current = basename($_SERVER['PHP_SELF']);
$flash = get_flash();
$notification_user_id = is_logged_in() ? (int)$_SESSION['user_id'] : 0;
$unread_notifications = $notification_user_id
    ? (int)q_val($conn, 'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', 'i', [$notification_user_id])
    : 0;
$recent_notifications = $notification_user_id
    ? q_all(
        $conn,
        'SELECT n.notification_id, n.read_at, r.reply_id, r.reply_body, m.subject, m.message
         FROM notifications n
         JOIN message_replies r ON r.reply_id = n.reply_id
         JOIN messages m ON m.message_id = r.message_id
         WHERE n.user_id = ?
         ORDER BY n.created_at DESC
         LIMIT 5',
        'i',
        [$notification_user_id]
    )
    : [];

function nav_link($file, $label, $current)
{
    $cls = ($file === $current) ? ' class="active"' : '';
    return '<li><a href="' . $file . '"' . $cls . '>' . $label . '</a></li>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | Acrobatic Tech Hub, Soroti City</title>
    <meta name="description" content="Acrobatic Tech Hub builds websites, digital systems and technology skills for businesses, schools and organisations in Soroti City and across Eastern Uganda.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?= (int)filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body>

<header class="site-header">
    <div class="site-topbar">
        <div class="container">
            <span class="topbar-location">
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z"></path><circle cx="12" cy="9" r="2.2"></circle></svg>
                Soroti City, Uganda
            </span>
            <a href="contact.php">Talk to our team <span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
    <nav class="navbar container">
        <a href="index.php" class="brand">
            <span class="brand-mark">A</span>
            <span class="brand-name"><span>ACROBATIC</span><em>TECH HUB</em></span>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <ul class="nav-links" id="navLinks">
            <?= nav_link('index.php', 'Home', $current) ?>
            <?= nav_link('about.php', 'About Us', $current) ?>
            <?= nav_link('projects.php', 'Projects', $current) ?>
            <?= nav_link('trainings.php', 'Trainings', $current) ?>
            <?= nav_link('contact.php', 'Contact', $current) ?>
            <?php if (is_logged_in()): ?>
                <li>
                    <details class="notification-menu">
                        <summary class="notification-link" aria-label="<?= $unread_notifications ? e($unread_notifications . ' unread notifications') : 'Notifications' ?>" title="Notifications">
                            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"></path></svg>
                            <?php if ($unread_notifications > 0): ?><span class="notification-count"><?= $unread_notifications > 99 ? '99+' : $unread_notifications ?></span><?php endif; ?>
                        </summary>
                        <div class="notification-panel">
                            <strong class="notification-heading">Recent replies</strong>
                            <?php if ($recent_notifications): ?>
                                <?php foreach ($recent_notifications as $notification): ?>
                                    <a class="notification-item<?= $notification['read_at'] === null ? ' unread' : '' ?>" href="profile.php#reply-<?= (int)$notification['reply_id'] ?>">
                                        <strong><?= e($notification['subject'] ?: 'Reply to your message') ?></strong>
                                        <span><b>Your message:</b> <?= e(mb_strimwidth($notification['message'], 0, 100, '...')) ?></span>
                                        <span><b>Admin reply:</b> <?= e(mb_strimwidth($notification['reply_body'], 0, 100, '...')) ?></span>
                                    </a>
                                <?php endforeach; ?>
                                <a class="notification-all" href="profile.php#replies">View all replies</a>
                            <?php else: ?>
                                <p class="notification-empty">No replies yet.</p>
                            <?php endif; ?>
                        </div>
                    </details>
                </li>
                <?= nav_link('dashboard.php', is_admin() ? 'Admin Dashboard' : 'My Dashboard', $current) ?>
                <?= nav_link('profile.php', 'My Profile', $current) ?>
                <li><a href="logout.php" class="btn btn-sm btn-primary nav-logout">Logout</a></li>
            <?php else: ?>
                <?= nav_link('login.php', 'Login', $current) ?>
                <li><a href="register.php" class="btn btn-sm btn-primary">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main>
<?php if ($flash): ?>
    <div class="container">
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
    </div>
<?php endif; ?>
