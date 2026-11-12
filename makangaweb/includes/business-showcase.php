<?php
require_once __DIR__ . '/business-demos.php';
?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Business website examples</span>
            <h2>Explore what your business website could do</h2>
            <p class="muted">Explore sample business websites with real stock photos and interactive menus, booking, appointment and produce-order features.</p>
        </div>
        <div class="grid-3">
            <?php foreach ($businessDemos as $slug => $business): ?>
                <article class="card">
                    <a class="business-demo-image" href="demo-business.php?business=<?= e($slug) ?>" aria-label="View the <?= e($business['name']) ?> website example">
                        <img src="<?= e($business['image']) ?>" alt="<?= e($business['alt']) ?>" loading="lazy">
                    </a>
                    <span class="tag"><?= e($business['category']) ?> · Example</span>
                    <h3><?= e($business['name']) ?></h3>
                    <p class="muted grow"><?= e($business['card_description']) ?></p>
                    <a class="btn btn-outline business-demo-link" href="demo-business.php?business=<?= e($slug) ?>">View website example</a>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="business-demo-note">These businesses and prices are fictional examples. Requests are saved for administrator review and are not confirmed orders or bookings.</p>
    </div>
</section>
