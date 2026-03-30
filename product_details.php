<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/includes/paths.php';
require_once __DIR__ . '/includes/db.php';

// Get package ID
$id = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : '';
$pkg = get_package_by_id($id);

if (!$pkg) {
    header('Location: ' . base_url('catalogue.php'));
    exit;
}

// Size options
$sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

// Extra guest pricing
$extra_guest_price = isset($pkg['extra_guest_price']) ? (float) $pkg['extra_guest_price'] : 0;
$default_guest_count = isset($pkg['default_guest_count']) ? (int) $pkg['default_guest_count'] : 100;
$max_guests = 180;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($pkg['title']); ?> | ChapterTwo</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #faf7f4;
            color: #1a1a1a;
            line-height: 1.5;
        }

        /* Header simulation */
        .site-header {
            border-bottom: 1px solid #eaeaea;
            background: #fff;
            padding: 0 2rem;
        }

        .header-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        /* Main container */
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px 60px;
        }

        /* Back button */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #000000;
        }

        /* GRID Layout - optimized for rectangular images */
        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
            padding: 20px 0 40px;
        }

        /* Left: Sticky image area - rectangular orientation */
        .image-section {
            position: sticky;
            top: 100px;
        }

        .main-image {
            width: 100%;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .main-image img {
            width: 100%;
            aspect-ratio: 16 / 10;
            /* Rectangular: 16:10 ratio (horizontal orientation) */
            object-fit: cover;
            background-color: #f5f0eb;
            display: block;
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 20px;
        }

        .thumb-item {
            overflow: hidden;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .thumb-item img {
            width: 100%;
            aspect-ratio: 16 / 11;
            object-fit: cover;
            background-color: #dcdcdc;
        }

        .thumb-item:hover {
            opacity: 0.8;
        }

        .thumb-active {
            outline: 2px solid #000;
            outline-offset: 2px;
        }

        /* Right side info section */
        .info-section {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .pkg-title {
            font-family: 'Georgia', serif;
            font-size: 36px;
            margin-bottom: 12px;
            font-weight: 400;
            letter-spacing: -0.3px;
        }

        .pkg-price {
            font-size: 28px;
            font-weight: 600;
            color: #000000;
            margin-bottom: 20px;
        }

        .base-tag {
            font-size: 14px;
            color: #999;
            font-weight: normal;
            margin-left: 8px;
        }

        .style-tags {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .style-tags .tag {
            border: 1px solid #e0e0e0;
            padding: 6px 18px;
            font-size: 12px;
            letter-spacing: 1px;
            background: #fafafa;
        }

        .stats-grid {
            margin: 25px 0;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 16px 0;
            font-size: 15px;
        }

        .stat-row strong {
            font-weight: 600;
            color: #000000;
        }

        /* Custom dropdown using details */
        .custom-dropdown {
            border-top: 1px solid #f0f0f0;
        }

        summary {
            list-style: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            letter-spacing: 0.5px;
        }

        summary::-webkit-details-marker {
            display: none;
        }

        summary i {
            transition: transform 0.3s ease;
            color: #000000;
        }

        details[open] summary i {
            transform: rotate(180deg);
        }

        .dropdown-content {
            padding-bottom: 20px;
        }

        .dropdown-content ul {
            padding-left: 20px;
            list-style: none;
        }

        .dropdown-content li {
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
            position: relative;
            padding-left: 20px;
        }

        .dropdown-content li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #000000;
            font-weight: bold;
        }

        /* Guest counter */
        .input-field {
            margin-bottom: 28px;
        }

        .input-field label {
            display: block;
            font-size: 13px;
            margin-bottom: 12px;
            color: #666;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stepper {
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid #e8e8e8;
            width: fit-content;
            background: #fff;
        }

        .stepper button {
            width: 44px;
            height: 44px;
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #666;
            transition: 0.2s;
        }

        .stepper button:hover {
            background: #f5f5f5;
            color: #000000;
        }

        .stepper input {
            width: 70px;
            text-align: center;
            border: none;
            font-size: 16px;
            font-weight: 500;
            padding: 8px 0;
            outline: none;
        }

        .stepper input::-webkit-inner-spin-button,
        .stepper input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .extra-guest-note {
            font-size: 12px;
            color: #000000;
            margin-top: 10px;
        }

        /* Size selector chips */
        .size-selector {
            margin-bottom: 28px;
        }

        .size-selector label {
            display: block;
            font-size: 13px;
            margin-bottom: 12px;
            color: #666;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .chip {
            cursor: pointer;
        }

        .chip input {
            display: none;
        }

        .chip span {
            display: inline-block;
            padding: 8px 20px;
            border: 1px solid #e0e0e0;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 56px;
            text-align: center;
            background: #fff;
            font-weight: 500;
        }

        .chip input:checked+span {
            background-color: #f8f7f7;
            color: #000000;
            border-color: #000000;
        }

        .chip span:hover {
            border-color: #000000;
        }

        /* Price summary */
        .price-summary {
            background: #faf9f8;
            padding: 24px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            color: #555;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: bold;
            border-top: 1px solid #e8e8e8;
            padding-top: 15px;
            margin-top: 8px;
            color: #1a1a1a;
        }

        .summary-row.extra-row {
            color: #000000;
        }

        .btn-add-to-cart {
            width: 100%;
            background: #000000;
            color: #fff;
            border: none;
            padding: 16px;
            font-size: 15px;
            letter-spacing: 1.5px;
            margin-top: 20px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 600;
        }

        .btn-add-to-cart:hover {
            transform: translateY(-2px);
        }

        /* Gallery section */
        .gallery-section {
            margin-top: 50px;
            padding-top: 60px;
            border-top: 1px solid #f0f0f0;
        }

        .gallery-title {
            font-size: 24px;
            font-weight: 400;
            margin-bottom: 50px;
            font-family: 'Georgia', serif;
            text-align: center;
            letter-spacing: 2px;
        }

        /* Related packages grid */
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 350px));
            gap: 24px;
            justify-content: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .related-card {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .related-img-wrap {
            overflow: hidden;
        }

        .related-img-wrap img {
            width: 100%;
            aspect-ratio: 16 / 10;
            object-fit: cover;
            background-color: #f5f0eb;
            transition: transform 0.3s;
        }

        .related-card:hover .related-img-wrap img {
            transform: scale(1.04);
        }

        .related-info {
            padding: 12px 0;
        }

        .related-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .related-price {
            font-size: 14px;
            color: #555;
        }

        @media (max-width: 768px) {
            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .related-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Responsive */
        @media (max-width: 968px) {
            .product-grid {
                grid-template-columns: 1fr;
                gap: 40px;
                padding: 20px 0;
            }

            .image-section {
                position: relative;
                top: 0;
            }

            .main-image img {
                aspect-ratio: 16 / 9;
            }

            .thumbnail-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 640px) {
            .thumbnail-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .pkg-title {
                font-size: 28px;
            }

            .pkg-price {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/includes/header_user.php'; ?>

    <main class="main-container">
        <a href="<?php echo base_url('catalogue.php'); ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Packages
        </a>

        <div class="product-grid">
            <!-- Left: Sticky image area (rectangular)  -->
            <div class="image-section">
                <div class="main-image">
                    <img src="<?php echo htmlspecialchars($pkg['image']); ?>"
                        alt="<?php echo htmlspecialchars($pkg['title']); ?>" id="mainImage">
                </div>
                <?php
                $gallery = is_string($pkg['gallery']) ? json_decode($pkg['gallery'], true) : $pkg['gallery'];
                // Build full thumbnail list: cover first, then gallery images (deduplicated)
                $allThumbs = [$pkg['image']];
                foreach ((array) $gallery as $gImg) {
                    if ($gImg !== $pkg['image'])
                        $allThumbs[] = $gImg;
                }
                if (count($allThumbs) > 1): ?>
                    <div class="thumbnail-grid">
                        <?php foreach ($allThumbs as $tIdx => $img): ?>
                            <div class="thumb-item <?php echo $tIdx === 0 ? 'thumb-active' : ''; ?>"
                                onclick="selectThumb(this, '<?php echo htmlspecialchars($img, ENT_QUOTES); ?>')">
                                <img src="<?php echo htmlspecialchars($img); ?>"
                                    alt="<?php echo htmlspecialchars($pkg['title']); ?> thumbnail <?php echo $tIdx + 1; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Information section -->
            <div class="info-section">
                <h1 class="pkg-title"><?php echo htmlspecialchars($pkg['title']); ?></h1>
                <p class="pkg-price">
                    RM <?php echo number_format($pkg['price'], 2); ?>
                    <span class="base-tag">base package</span>
                </p>

                <div class="style-tags">
                    <span class="tag"><?php echo strtoupper(htmlspecialchars($pkg['culture'])); ?></span>
                    <span class="tag"><?php echo strtoupper(htmlspecialchars($pkg['venue'])); ?></span>
                </div>

                <div class="stats-grid">
                    <div class="stat-row">
                        <span>BASE GUESTS</span>
                        <strong><?php echo $default_guest_count; ?> included</strong>
                    </div>
                    <div class="stat-row">
                        <span>MAXIMUM CAPACITY</span>
                        <strong><?php echo $max_guests; ?> guests</strong>
                    </div>
                </div>

                <form action="<?php echo base_url('cart_add_db.php'); ?>" method="POST" id="packageForm">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($pkg['id']); ?>">
                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($pkg['title']); ?>">
                    <input type="hidden" name="base_price" value="<?php echo $pkg['price']; ?>" id="basePrice">
                    <input type="hidden" name="final_price" value="<?php echo $pkg['price']; ?>" id="finalPrice">

                    <!-- DETAILS dropdown -->
                    <details class="custom-dropdown">
                        <summary>
                            DETAILS
                            <i class="fas fa-chevron-down"></i>
                        </summary>
                        <div class="dropdown-content">
                            <ul>
                                <?php
                                $features = is_string($pkg['features']) ? json_decode($pkg['features'], true) : $pkg['features'];
                                if (!empty($features)):
                                    foreach ((array) $features as $f): ?>
                                        <li><?php echo htmlspecialchars($f); ?></li>
                                    <?php endforeach;
                                else: ?>
                                    <li>Elegant venue with premium decoration</li>
                                    <li>Professional photography & videography</li>
                                    <li>5-course gourmet catering service</li>
                                    <li>Full wedding coordination team</li>
                                    <li>Bridal makeup & hair styling</li>
                                    <li>Floral arrangements & centerpieces</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </details>

                    <!-- CUSTOMIZE dropdown (open by default) -->
                    <details class="custom-dropdown" open>
                        <summary>
                            CUSTOMIZE
                            <i class="fas fa-chevron-down"></i>
                        </summary>
                        <div class="dropdown-content">
                            <!-- Guest count selector -->
                            <div class="input-field">
                                <label>NUMBER OF GUESTS</label>
                                <div class="stepper">
                                    <button type="button" id="guestMinus">−</button>
                                    <input type="number" name="guest_count" id="guestCount"
                                        value="<?php echo $default_guest_count; ?>" min="1"
                                        max="<?php echo $max_guests; ?>">
                                    <button type="button" id="guestPlus">+</button>
                                </div>
                                <div class="extra-guest-note" id="extraGuestNote"></div>
                            </div>

                            <!-- Bride's dress size -->
                            <div class="size-selector">
                                <label>BRIDAL DRESS SIZE</label>
                                <div class="chip-group">
                                    <?php foreach ($sizes as $s): ?>
                                        <label class="chip">
                                            <input type="radio" name="bride_size" value="<?php echo $s; ?>" <?php echo $s === 'M' ? 'checked' : ''; ?>>
                                            <span><?php echo $s; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Groom's suit size -->
                            <div class="size-selector">
                                <label>GROOM SUIT SIZE</label>
                                <div class="chip-group">
                                    <?php foreach ($sizes as $s): ?>
                                        <label class="chip">
                                            <input type="radio" name="groom_size" value="<?php echo $s; ?>" <?php echo $s === 'M' ? 'checked' : ''; ?>>
                                            <span><?php echo $s; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </details>

                    <!-- Price summary -->
                    <div class="price-summary">
                        <div class="summary-row">
                            <span>Package base</span>
                            <span>RM <?php echo number_format($pkg['price'], 2); ?></span>
                        </div>
                        <div class="summary-row extra-row" id="extraGuestRow" style="display: none;">
                            <span>Extra guests</span>
                            <span id="extraGuestAmount">RM 0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="subtotalAmount">RM <?php echo number_format($pkg['price'], 2); ?></span>
                        </div>
                    </div>

                    <button type="submit" class="btn-add-to-cart">
                        ADD TO CART
                    </button>
                </form>
            </div>
        </div>

        <!-- Related packages recommendation -->
        <?php
        $related = get_related_packages($pkg['culture'], $pkg['id'], 3);
        if (!empty($related)):
            ?>
            <div class="gallery-section">
                <div class="gallery-card">
                <h3 class="gallery-title">You may also consider</h3>
                <div class="related-grid">
                    <?php foreach ($related as $rp): ?>
                        <a href="<?php echo base_url('product_details.php?id=' . urlencode($rp['id'])); ?>"
                            class="related-card">
                            <div class="related-img-wrap">
                                <img src="<?php echo htmlspecialchars($rp['image']); ?>"
                                    alt="<?php echo htmlspecialchars($rp['title']); ?>">
                            </div>
                            <div class="related-info">
                                <p class="related-title"><?php echo htmlspecialchars($rp['title']); ?></p>
                                <p class="related-price">RM <?php echo number_format($rp['price'], 2); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
        <?php endif; ?>
    </main>

    <script>
        (function () {
            const basePrice = <?php echo (float) $pkg['price']; ?>;
            const defaultGuests = <?php echo (int) $default_guest_count; ?>;
            const maxGuests = <?php echo (int) $max_guests; ?>;
            const extraGuestPrice = <?php echo (float) $extra_guest_price; ?>;

            const guestInput = document.getElementById('guestCount');
            const guestMinus = document.getElementById('guestMinus');
            const guestPlus = document.getElementById('guestPlus');
            const extraGuestRow = document.getElementById('extraGuestRow');
            const extraGuestAmount = document.getElementById('extraGuestAmount');
            const subtotalAmount = document.getElementById('subtotalAmount');
            const finalPriceInput = document.getElementById('finalPrice');
            const extraGuestNote = document.getElementById('extraGuestNote');

            function updatePrice() {
                let guestCount = parseInt(guestInput.value) || defaultGuests;

                if (guestCount < 1) guestCount = 1;
                if (guestCount > maxGuests) guestCount = maxGuests;
                guestInput.value = guestCount;

                let extraGuests = 0;
                let extraTotal = 0;

                if (guestCount > defaultGuests && extraGuestPrice > 0) {
                    extraGuests = guestCount - defaultGuests;
                    extraTotal = extraGuests * extraGuestPrice;
                    extraGuestRow.style.display = 'flex';
                    extraGuestAmount.innerText = 'RM ' + extraTotal.toLocaleString('en-MY', { minimumFractionDigits: 2 });
                    extraGuestNote.innerText = '+' + extraGuests + ' extra guest(s) × RM ' + extraGuestPrice.toFixed(2);
                } else {
                    extraGuestRow.style.display = 'none';
                    extraGuestNote.innerText = '';
                }

                const finalTotal = basePrice + extraTotal;
                subtotalAmount.innerText = 'RM ' + finalTotal.toLocaleString('en-MY', { minimumFractionDigits: 2 });
                if (finalPriceInput) {
                    finalPriceInput.value = finalTotal;
                }
            }

            if (guestMinus) {
                guestMinus.addEventListener('click', function () {
                    let val = parseInt(guestInput.value) || defaultGuests;
                    if (val > 1) {
                        guestInput.value = val - 1;
                        updatePrice();
                    }
                });
            }

            if (guestPlus) {
                guestPlus.addEventListener('click', function () {
                    let val = parseInt(guestInput.value) || defaultGuests;
                    if (val < maxGuests) {
                        guestInput.value = val + 1;
                        updatePrice();
                    }
                });
            }

            if (guestInput) {
                guestInput.addEventListener('change', updatePrice);
                guestInput.addEventListener('input', updatePrice);
            }

            updatePrice();

            const form = document.getElementById('packageForm');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const brideSelected = document.querySelector('input[name="bride_size"]:checked');
                    const groomSelected = document.querySelector('input[name="groom_size"]:checked');

                    if (!brideSelected || !groomSelected) {
                        e.preventDefault();
                        alert('Please select both bridal dress size and groom suit size.');
                    }
                });
            }
        })();

        function selectThumb(el, src) {
            document.getElementById('mainImage').src = src;
            document.querySelectorAll('.thumb-item').forEach(function (t) {
                t.classList.remove('thumb-active');
            });
            el.classList.add('thumb-active');
        }
    </script>

</body>

</html>