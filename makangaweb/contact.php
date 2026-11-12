<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Contact';

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

// Pre-fill for logged-in clients
if (is_logged_in()) {
    $old['name'] = $_SESSION['full_name'] ?? '';
    $old['email'] = $_SESSION['email'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['msg'])) {
    $old['message'] = mb_substr(trim((string)$_GET['msg']), 0, 1000);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = trim($_POST['name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['subject'] = trim($_POST['subject'] ?? '');
    $old['message'] = trim($_POST['message'] ?? '');

    if (!csrf_ok()) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if (mb_strlen($old['name']) < 2) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (mb_strlen($old['message']) < 10) {
        $errors[] = 'Your message should be at least 10 characters.';
    }

    if (!$errors) {
        q_exec(
            $conn,
            'INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)',
            'ssss',
            [mb_substr($old['name'], 0, 100), mb_substr($old['email'], 0, 120), mb_substr($old['subject'], 0, 150), $old['message']]
        );
        flash('success', 'Thank you! Your message has been sent. We will reply soon.');
        redirect('contact.php');
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="eyebrow">Get in touch</span>
        <h1>Contact us</h1>
        <p>Tell us about your project, or ask about our trainings. We reply within one working day.</p>
    </div>
</section>

<div class="container">
    <div class="contact-grid">
        <div>
            <h2>Let's talk</h2>
            <p class="muted">Get in touch with our team by phone or send a message using the form.</p>

            <div class="info-item"><div class="icon">&#128205;</div><div><b>Address</b><span class="muted">Soroti City, Teso Sub-region, Uganda</span></div></div>
            <div class="info-item"><div class="icon">&#128222;</div><div><b>Phone / WhatsApp</b><span class="muted">+256 700 000 000</span></div></div>
            <div class="info-item"><div class="icon">&#9993;</div><div><b>Email</b><span class="muted">info@makangaweb.example</span></div></div>
            <div class="info-item"><div class="icon">&#128339;</div><div><b>Working hours</b><span class="muted">Monday - Saturday, 8:00 AM - 6:00 PM</span></div></div>
        </div>

        <div class="form-card">
            <h2 style="font-size:1.5rem">Send us a message</h2>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    Please fix the following:
                    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" action="contact.php" id="contactForm" novalidate>
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Your name</label>
                        <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" value="<?= e($old['subject']) ?>" placeholder="e.g. New website for my shop">
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required><?= e($old['message']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send message</button>
            </form>
        </div>
    </div>

    <iframe class="map" title="Map of Soroti City" loading="lazy"
        src="https://www.openstreetmap.org/export/embed.html?bbox=33.56%2C1.68%2C33.66%2C1.75&amp;layer=mapnik&amp;marker=1.7147%2C33.6111"></iframe>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
