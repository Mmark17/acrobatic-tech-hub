<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Projects';

$projects = q_all($conn, 'SELECT * FROM projects ORDER BY (status = \'Completed\') DESC, date_completed DESC, project_id DESC');

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="eyebrow">Our portfolio</span>
        <h1>Projects completed</h1>
        <p>A selection of websites and systems we have designed and built.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($projects): ?>
            <div class="grid-3">
                <?php foreach ($projects as $p): ?>
                    <article class="card">
                        <figure class="project-media">
                            <img src="<?= e(project_image($p['title'])) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
                        </figure>
                        <div style="display:flex;justify-content:space-between;gap:.5rem;align-items:start">
                            <h3><?= e($p['title']) ?></h3>
                            <span class="badge <?= $p['status'] === 'Completed' ? 'badge-ok' : 'badge-warn' ?>"><?= e($p['status']) ?></span>
                        </div>
                        <p class="muted grow"><?= e($p['description']) ?></p>
                        <?php if (!empty($p['technologies'])): ?>
                            <div class="tags">
                                <?php foreach (explode(',', $p['technologies']) as $tech): ?>
                                    <span class="tag"><?= e(trim($tech)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="card-footer">
                            <small class="muted">Client: <?= e($p['client_name'] ?: 'Confidential') ?></small>
                            <small class="muted"><?= $p['date_completed'] ? e(date('M Y', strtotime($p['date_completed']))) : 'In progress' ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty">No projects have been added yet.</p>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/business-showcase.php'; ?>

<section class="section">
    <div class="container">
        <div class="cta">
            <div>
                <h2>Want a project like these?</h2>
                <p>Let's talk about your idea and budget.</p>
            </div>
            <a href="contact.php" class="btn btn-dark">Start a project</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
