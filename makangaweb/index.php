<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Home';

$stats = [
    'projects'  => q_val($conn, 'SELECT COUNT(*) FROM projects'),
    'clients'   => q_val($conn, "SELECT COUNT(*) FROM users WHERE role = 'client'"),
    'trainings' => q_val($conn, 'SELECT COUNT(*) FROM trainings'),
    'learners'  => q_val($conn, 'SELECT COUNT(*) FROM enrolments'),
];
$featured = q_all($conn, 'SELECT * FROM projects ORDER BY project_id DESC LIMIT 3');

include __DIR__ . '/includes/header.php';
?>

<section class="hero home-hero" aria-labelledby="home-title">
    <div class="container hero-grid home-hero-grid">
        <div class="home-hero-copy">
            <span class="eyebrow">Web design &amp; development · Soroti City</span>
            <h1 id="home-title">Digital experiences that move your business <span>forward.</span></h1>
            <p class="lead">We build practical websites, web systems and digital skills for businesses, schools and organisations across Eastern Uganda.</p>
            <div class="hero-actions">
                <a href="contact.php" class="btn btn-primary">Start a project</a>
                <a href="projects.php" class="btn btn-outline-light">Explore our work</a>
            </div>
        </div>
        <div class="home-visual">
            <img src="images/workspace.jpg" alt="Web developer working on a website at a computer" fetchpriority="high">
            <div class="home-visual-note">
                <span class="home-visual-mark" aria-hidden="true">M</span>
                <span><strong>Built around your next step</strong><small>Responsive, useful and ready to grow.</small></span>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <div class="stats">
        <div class="stat"><b><?= (int)$stats['projects'] ?></b><span>Projects built</span></div>
        <div class="stat"><b><?= (int)$stats['clients'] ?></b><span>Registered clients</span></div>
        <div class="stat"><b><?= (int)$stats['trainings'] ?></b><span>Training courses</span></div>
        <div class="stat"><b><?= (int)$stats['learners'] ?></b><span>Enrolments</span></div>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="section-head center">
            <span class="eyebrow">What we do</span>
            <h2>Everything your business needs online</h2>
            <p class="muted">From a simple business page to a full database-driven system, built to load fast on any phone.</p>
        </div>
        <div class="grid-3">
            <article class="card">
                <div class="icon">&#127760;</div>
                <h3>Business websites</h3>
                <p class="muted grow">Professional, responsive sites for shops, hotels, schools and clinics, with your branding and contact details front and centre.</p>
            </article>
            <article class="card">
                <div class="icon">&#9881;</div>
                <h3>Dynamic web systems</h3>
                <p class="muted grow">Login, user accounts, dashboards and databases using PHP and MySQL, for example booking, payment or management systems.</p>
            </article>
            <article class="card">
                <div class="icon">&#127891;</div>
                <h3>Technical skills training</h3>
                <p class="muted grow">Hands-on courses in web development, SolidWorks mechanical design and KiCad PCB design for students and professionals.</p>
            </article>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/business-showcase.php'; ?>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Featured work</span>
            <h2>Recent projects</h2>
        </div>
        <?php if ($featured): ?>
            <div class="grid-3">
                <?php foreach ($featured as $p): ?>
                    <article class="card">
                        <figure class="project-media">
                            <img src="<?= e(project_image($p['title'])) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
                        </figure>
                        <h3><?= e($p['title']) ?></h3>
                        <p class="muted grow"><?= e($p['description']) ?></p>
                        <div class="card-footer">
                            <span class="badge <?= $p['status'] === 'Completed' ? 'badge-ok' : 'badge-warn' ?>"><?= e($p['status']) ?></span>
                            <small class="muted"><?= e($p['technologies']) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty">Projects will appear here soon.</p>
        <?php endif; ?>
        <p style="margin-top:2rem"><a href="projects.php" class="btn btn-dark">View all projects</a></p>
    </div>
</section>

<section class="section section-dark">
    <div class="container spotlight">
        <div>
            <span class="eyebrow">Research spotlight</span>
            <h2>VoltPay: a smart prepaid power and rent system</h2>
            <p class="muted">Our research project shows what real, data-driven web systems can do for everyday problems in Uganda: tracking prepaid electricity use and managing rent for landlords and tenants in one place.</p>
            <ul class="pill-list">
                <li>Monitors prepaid power consumption per unit</li>
                <li>Records and tracks tenant rent payments</li>
                <li>Web dashboard for landlords and tenants</li>
                <li>Built with PHP, MySQL and ESP32 hardware</li>
            </ul>
            <a href="projects.php" class="btn btn-primary">See the project</a>
        </div>
        <div class="flow">
            <div class="flow-step"><b>1</b><div><strong>Sense</strong><br><span class="muted">Meter data is read by an ESP32 device</span></div></div>
            <div class="flow-step"><b>2</b><div><strong>Store</strong><br><span class="muted">Readings and payments go to a MySQL database</span></div></div>
            <div class="flow-step"><b>3</b><div><strong>Show</strong><br><span class="muted">Landlords and tenants see live balances online</span></div></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta">
            <div>
                <h2>Ready to put your business online?</h2>
                <p>Tell us what you need and get a free quote within 24 hours.</p>
            </div>
            <div class="hero-actions">
                <a href="contact.php" class="btn btn-dark">Contact us</a>
                <?php if (!is_logged_in()): ?>
                    <a href="register.php" class="btn btn-outline">Create an account</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
