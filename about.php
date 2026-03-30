<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/paths.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | ChapterTwo</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/about.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda&family=Bonheur+Royale&family=DM+Serif+Text&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
<?php include 'includes/header_user.php'; ?>

<section class="about-hero">
    <div class="section-content">
        <h1>About Us</h1>
        <p>At <strong>ChapterTwo</strong>, we believe that every love story deserves a breathtaking second chapter. Based in Malacca, we specialize in transforming your dreams into unforgettable wedding celebrations. Our team is dedicated to crafting magical beginnings.</p>
    </div>
</section>

<section class="team-bg-wrapper">
    <div class="section-content">
        <h2 class="section-title">Meet Our Team</h2>
        <div class="teams-card">
            <div class="team-member">
                <img src="<?php echo base_url('assets/images/home/YZ.jpeg'); ?>" alt="Lee Yu Zhen">
                <h3>Lee Yu Zhen</h3>
                <p style="color:#888;font-size:0.9rem;margin-top:8px;">Wedding Coordination & Design</p>
            </div>
            <div class="team-member">
                <img src="<?php echo base_url('assets/images/home/Shannon.jpeg'); ?>" alt="Shannon Yew Jia Chi">
                <h3>Shannon Yew Jia Chi</h3>
                <p style="color:#888;font-size:0.9rem;margin-top:8px;">Customer Relations & Planning</p>
            </div>
            <div class="team-member">
                <img src="<?php echo base_url('assets/images/home/LX.jpeg'); ?>" alt="Tan Le Xuan">
                <h3>Tan Le Xuan</h3>
                <p style="color:#888;font-size:0.9rem;margin-top:8px;">Events & Operations</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>
