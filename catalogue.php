<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/paths.php';
require_once __DIR__ . '/includes/db.php';
$culture = isset($_GET['c']) ? preg_replace('/[^a-z_]/', '', $_GET['c']) : null;
$packageData = get_packages($culture);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Packages | ChapterTwo</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/our_package.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Playfair+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>body{background:#fff;}</style>
</head>
<body>
<?php include 'includes/header_user.php'; ?>

<div class="container">
    <h1 class="decorative-heading">Discover Your Dream Packages</h1>
    <div class="filter-box">
        <div class="search-wrapper">
            <input type="text" id="searchInput" placeholder="Search by name, budget, theme..." oninput="filterPackages()">
        </div>
        <div class="filter-bar">
            <div class="filter-container">
                <div class="filters">
                    <select id="CulturalTradition"><option value="all">Cultural Tradition</option><option value="western">Western</option><option value="chinese">Chinese</option><option value="malay">Malay</option><option value="indian">Indian</option><option value="fusion">Fusion</option></select>
                    <select id="VenueType"><option value="all">Venue</option><option value="indoor">Indoor</option><option value="outdoor">Outdoor</option></select>
                    <select id="PriceRange"><option value="all">Price</option><option value="15000">≤ RM15,000</option><option value="30000">≤ RM30,000</option><option value="50000">≤ RM50,000</option></select>
                    <select id="GuestCount"><option value="all">Guests</option><option value="small">Below 50</option><option value="medium">100-300</option><option value="large">400+</option></select>
                </div>
                <button id="resetBtn">Clear</button>
            </div>
        </div>
    </div>
    <br><br>
    <div class="package-card" id="package-container"></div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
window.PRODUCT_DETAILS_BASE = <?php echo json_encode(base_url('product_details.php')); ?>;
window.CATALOGUE_PACKAGE_DATA = <?php echo json_encode($packageData); ?>;
</script>
<script src="<?php echo base_url('assets/js/catalogue.js'); ?>"></script>
</body>
</html>
