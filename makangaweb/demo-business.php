<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/business-demos.php';

$slug = $_GET['business'] ?? '';
if (!is_string($slug) || !isset($businessDemos[$slug])) {
    http_response_code(404);
    $page_title = 'Business example not found';
    include __DIR__ . '/includes/header.php';
    ?>
    <section class="section">
        <div class="container">
            <div class="alert alert-info" role="status">That business example could not be found.</div>
            <a class="btn btn-dark" href="projects.php">Browse business examples</a>
        </div>
    </section>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

$demo = $businessDemos[$slug];

function demo_request_date_is_valid($value)
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash('error', 'Your session expired. Please try again.');
        redirect('demo-business.php?business=' . rawurlencode($slug) . '#demo-request');
    }

    $nameValue = $_POST['name'] ?? '';
    $emailValue = $_POST['email'] ?? '';
    $phoneValue = $_POST['phone'] ?? '';
    $dateValue = $_POST['date'] ?? '';
    $checkoutValue = $_POST['checkout'] ?? '';
    $scalarValues = [$nameValue, $emailValue, $phoneValue, $dateValue, $checkoutValue];
    $hasScalarValues = count(array_filter($scalarValues, 'is_string')) === count($scalarValues);
    $name = $hasScalarValues ? trim($nameValue) : '';
    $email = $hasScalarValues ? trim($emailValue) : '';
    $phone = $hasScalarValues ? trim($phoneValue) : '';
    $requestedDate = $hasScalarValues ? trim($dateValue) : '';
    $checkoutDate = $hasScalarValues ? trim($checkoutValue) : '';
    $postedItems = $_POST['items'] ?? null;
    $actionTypes = [
        'order' => 'order',
        'booking' => 'booking',
        'appointment' => 'appointment',
        'produce order' => 'produce_order',
    ];
    $today = date('Y-m-d');
    $valid = $hasScalarValues
        && $name !== ''
        && mb_strlen($name) <= 100
        && filter_var($email, FILTER_VALIDATE_EMAIL)
        && mb_strlen($email) <= 120
        && mb_strlen($phone) <= 20
        && demo_request_date_is_valid($requestedDate)
        && $requestedDate >= $today
        && is_array($postedItems)
        && count($postedItems) > 0
        && count($postedItems) <= count($demo['items']);

    $nights = null;
    if ($demo['action'] === 'booking') {
        $valid = $valid
            && demo_request_date_is_valid($checkoutDate)
            && $checkoutDate > $requestedDate;
        if ($valid) {
            $nights = (int)((strtotime($checkoutDate) - strtotime($requestedDate)) / 86400);
            $valid = $nights >= 1 && $nights <= 365;
        }
    } elseif ($checkoutDate !== '') {
        $valid = false;
    }

    $requestItems = [];
    $estimatedTotal = 0;
    if ($valid) {
        foreach ($postedItems as $index => $quantityValue) {
            $filterOptions = ['options' => ['min_range' => 1, 'max_range' => 50]];
            if (!is_string($quantityValue) && !is_int($quantityValue)) {
                $valid = false;
                break;
            }
            $quantity = filter_var($quantityValue, FILTER_VALIDATE_INT, $filterOptions);
            if (!is_string($index) && !is_int($index)) {
                $valid = false;
                break;
            }
            $itemIndex = filter_var((string)$index, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($quantity === false || $itemIndex === false || !isset($demo['items'][$itemIndex])) {
                $valid = false;
                break;
            }

            $item = $demo['items'][$itemIndex];
            $lineTotal = $item['price'] * $quantity * ($nights ?? 1);
            $estimatedTotal += $lineTotal;
            $requestItems[] = [
                'name' => $item['name'],
                'quantity' => $quantity,
                'unit_price' => $item['price'],
                'line_total' => $lineTotal,
            ];
        }
    }

    if (!$valid) {
        flash('error', 'Please check your contact details, dates and selected items, then try again.');
        redirect('demo-business.php?business=' . rawurlencode($slug) . '#demo-request');
    }

    try {
        $conn->begin_transaction();
        $stmt = q_run(
            $conn,
            'INSERT INTO business_demo_requests
                (business_slug, request_type, customer_name, customer_email, customer_phone, requested_date, checkout_date, stay_nights, estimated_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'sssssssii',
            [
                $slug,
                $actionTypes[$demo['action']],
                $name,
                $email,
                $phone === '' ? null : $phone,
                $requestedDate,
                $checkoutDate === '' ? null : $checkoutDate,
                $nights,
                $estimatedTotal,
            ]
        );
        $stmt->close();
        $requestId = (int)$conn->insert_id;

        foreach ($requestItems as $item) {
            q_exec(
                $conn,
                'INSERT INTO business_demo_request_items
                    (request_id, item_name, quantity, unit_price, line_total)
                 VALUES (?, ?, ?, ?, ?)',
                'isiii',
                [$requestId, $item['name'], $item['quantity'], $item['unit_price'], $item['line_total']]
            );
        }

        $conn->commit();
        flash('success', 'Sample request #' . $requestId . ' saved. It is not a confirmed booking, appointment or order.');
        redirect('demo-business.php?business=' . rawurlencode($slug) . '#demo-request');
    } catch (mysqli_sql_exception $ex) {
        $conn->rollback();
        error_log('Business demo request save failed: ' . $ex->getMessage());
        flash('error', 'We could not save your request right now. Please try again later.');
        redirect('demo-business.php?business=' . rawurlencode($slug) . '#demo-request');
    }
}

$page_title = $demo['name'];
include __DIR__ . '/includes/header.php';
?>

<section class="business-demo-hero">
    <div class="container business-demo-hero-grid">
        <div class="business-demo-copy">
            <span class="eyebrow"><?= e($demo['category']) ?> · Website example</span>
            <h1><?= e($demo['headline']) ?></h1>
            <p class="lead"><?= e($demo['description']) ?></p>
            <p class="business-demo-disclaimer">This is a fictional business example. Requests are saved for administrator review; no booking or order is confirmed.</p>
            <a class="btn btn-primary" href="#demo-items">Explore <?= $demo['action'] === 'booking' ? 'rooms' : ($demo['action'] === 'appointment' ? 'services' : ($demo['action'] === 'produce order' ? 'produce' : 'menu')) ?></a>
        </div>
        <figure class="business-demo-hero-image">
            <img src="<?= e($demo['image']) ?>" alt="<?= e($demo['alt']) ?>" fetchpriority="high">
            <figcaption>Stock photo via <a href="https://unsplash.com/" target="_blank" rel="noopener noreferrer"><?= e($demo['photo_credit']) ?></a></figcaption>
        </figure>
    </div>
</section>

<section class="section" id="demo-items">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Explore the menu</span>
            <h2><?= e($demo['items_heading']) ?></h2>
            <p class="muted">Prices and availability are sample data for this interactive preview.</p>
        </div>
        <div class="grid-3">
            <?php foreach ($demo['items'] as $index => $item): ?>
                <article class="card business-demo-product">
                    <span class="tag"><?= e($demo['category']) ?></span>
                    <h3><?= e($item['name']) ?></h3>
                    <p class="muted grow"><?= e($item['description']) ?></p>
                    <div class="business-demo-product-footer">
                        <strong>UGX <?= number_format($item['price']) ?><?= e($demo['pricing_unit']) ?></strong>
                        <button class="btn btn-sm btn-outline" type="button" data-demo-add data-index="<?= (int)$index ?>">Add</button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt" id="demo-request">
    <div class="container">
        <div class="business-demo-checkout">
            <div class="business-demo-cart">
                <span class="eyebrow">Interactive preview</span>
                <h2><?= e($demo['action_heading']) ?></h2>
                <p class="muted"><?= e($demo['action_description']) ?></p>
                <?php if ($demo['action'] === 'booking'): ?>
                    <p class="business-demo-hint">The estimate updates by room and number of nights.</p>
                <?php endif; ?>
                <ul class="business-demo-cart-items" id="demoCartItems" aria-live="polite">
                    <li class="business-demo-empty">Your selection is empty. Add an item to get started.</li>
                </ul>
                <div class="business-demo-total">
                    <span><?= $demo['action'] === 'booking' ? 'Estimated stay' : 'Estimated total' ?></span>
                    <strong id="demoTotal">UGX 0</strong>
                </div>
            </div>
            <div class="business-demo-form-panel">
                <h2>Send a request</h2>
                <p class="muted">Send a request for the site administrator to review. This does not confirm a booking, appointment or order.</p>
                <form id="demoRequestForm" method="post" action="demo-business.php?business=<?= e($slug) ?>#demo-request" data-demo-action="<?= e($demo['action']) ?>" data-items='<?= e(json_encode($demo['items'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>'>
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="demoName">Your name</label>
                        <input id="demoName" name="name" type="text" autocomplete="name" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="demoEmail">Email address</label>
                        <input id="demoEmail" name="email" type="email" autocomplete="email" required maxlength="120">
                    </div>
                    <div class="form-group">
                        <label for="demoPhone">Phone number <span class="muted">(optional)</span></label>
                        <input id="demoPhone" name="phone" type="tel" autocomplete="tel" maxlength="20">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="demoDate"><?= e($demo['date_label']) ?></label>
                            <input id="demoDate" name="date" type="date" min="<?= e(date('Y-m-d')) ?>" required>
                        </div>
                        <?php if ($demo['action'] === 'booking'): ?>
                            <div class="form-group">
                                <label for="demoCheckout"><?= e($demo['checkout_label']) ?></label>
                                <input id="demoCheckout" name="checkout" type="date" min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>" required>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div id="demoCartFields"></div>
                    <button class="btn btn-primary" type="submit">Send request</button>
                </form>
                <div class="business-demo-result" id="demoResult" role="status" aria-live="polite" hidden></div>
                <p class="business-demo-disclaimer">Your name, email and request details will be saved in this site's database and visible to its administrators. Do not submit sensitive information.</p>
                <noscript><p class="business-demo-disclaimer">Enable JavaScript to select items. Nothing is submitted unless you choose items and send the request.</p></noscript>
            </div>
        </div>
        <p class="business-demo-back"><a href="projects.php">&larr; Back to business examples</a></p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
