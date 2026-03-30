<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/paths.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChapterTwo | Wedding Planner</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/home.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/index-storyflow.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900&family=Bonheur+Royale&family=DM+Serif+Text:ital@0;1&family=Nanum+Myeongjo:wght@800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Corinthia:wght@400;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Bonheur+Royale&family=Corinthia:wght@400;700&family=DM+Serif+Text:ital@0;1&family=EB+Garamond:ital,wght@0,400..800;1,400..800&family=Nanum+Myeongjo:wght@800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
</head>

<body class="index-page">
<?php include 'includes/header_user.php'; ?>

<button id="wm-back-to-top" aria-label="Back to top">
    <div class="btt-background"></div>
    <a class="icon" href="#"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path fill="none" stroke="#202020" stroke-width="2" d="M32 10v46"/><path fill="none" stroke="#202020" stroke-width="2" d="M50 20 L32 4 14 20"/></svg></a>
</button>

<main class="story-flow">
    <section class="hero-slide story-block">
        <section class="hero-section">
            <div class="section-content hero-content rellax" data-aos="fade-up" data-rellax-speed="-1.5">
                <div class="hero-details">
                    <h2 class="title" data-aos="fade-up" data-aos-delay="40" style="font-family: var(--font-logo); color: #efe0d4; font-size: 70px;">The Key to Happiness</h2>
                    <h3 class="subtitle" data-aos="fade-up" data-aos-delay="110" style="font-size:27px;color:#efe0d4;">Where Dreams Become Forever</h3>
                    <p class="description" data-aos="fade-up" data-aos-delay="180" style="color:grey;">The first chapter was your story,<br>now begins the celebration of your forever</p>
                    <div class="button" data-aos="fade-up" data-aos-delay="260">
                        <a href="<?php echo base_url('register.php'); ?>" class="btn login">Get Started</a>
                        <a href="#contact-section" class="btn contact-us">Contact Us</a>
                    </div>
                </div>
            </div>
        </section>
    </section>

    <section class="story-block">
        <section class="dreamday-section">
            <div class="about-header" data-aos="fade-up">
                <h2 class="header-text">
                     <span class="highlight">YOUR LOVE, OUR CRAFT</span>
                </h2>
            </div>

            <div class="about-body" data-aos="fade-up" data-aos-delay="80">
                <div class="about-description">
                    <p>Here, we understand that your wedding day is a chapter in your love story, and we are here to ensure it is a masterpiece. With years of expertise in orchestrating dream weddings, we have earned a reputation for creating unforgettable love moments.</p>
                    <div>
                        <p>Our journey is woven with a passion for love, design, and meticulous planning. We are professional memory curators dedicated to making your special day a reflection of your love story.</p>
                        <div class="about-signature">
                            <span>With love,</span>
                            <img src="assets/images/home/sign1.png" alt="Signature" class="signature-img">
                        </div>
                    </div>
                </div>
            </div>

            <div class="about-visuals" data-aos="fade-up" data-aos-delay="140">
                <div class="left-image-wrap rellax" data-rellax-speed="1.2">
                    <img src="assets/images/home/wedding1.jpg" alt="Bride and groom portrait">
                </div>

                        <div class="right-image-wrap rellax" data-rellax-speed="0.8">
                            <img src="assets/images/home/wedding2.jpg" alt="Bride and groom portrait">
                        </div>

                        <div class="bottom-title">
                                    <span class="tag">FOR YOUR</span>
                            <h3 class="main-tag">DREAMDAY</h3>
                        </div>
                    </div>
                </section>
            </section>

    <section class="services-section story-block" data-aos="fade-up" data-aos-delay="160">
        <h2 class="section-title-center" style="font-weight: bold;">OUR SERVICES</h2>

                <div class="services-container">
                    <div class="service-card photo-card" data-aos="fade-up">
                        <img src="https://thesimplyelegantgroup.com/wp-content/uploads/2023/12/french-farmhouse-wedding-ceremony-collinsville-tx-1024x684.jpg" alt="Venue">
                        <div class="card-label">VENUE</div>
                    </div>

            <div class="service-card photo-card" data-aos="fade-up" data-aos-delay="60">
                <img src="https://images.pexels.com/photos/2814828/pexels-photo-2814828.jpeg" alt="Catering">
                <div class="card-label">CATERING</div>
            </div>

            <div class="service-card photo-card" data-aos="fade-up" data-aos-delay="120">
                <img src="https://images.pexels.com/photos/7711167/pexels-photo-7711167.jpeg" alt="Decor">
                <div class="card-label">DECOR</div>
            </div>

            <div class="service-card photo-card" data-aos="fade-up">
                <img src="https://images.pexels.com/photos/5729053/pexels-photo-5729053.jpeg" alt="Attire">
                <div class="card-label">ATTIRE</div>
            </div>

            <div class="service-card info-card" data-aos="fade-up" data-aos-delay="80">
                <div class="info-inner">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <h3>AI Planner</h3>
                    <p>Skip the stress of planning. Our intelligent AI curator organizes every detail to match your unique love story.</p>
                    <a href="<?php echo base_url('catalogue.php'); ?>" class="btn-book">BOOK NOW</a>
                </div>
            </div>

            <div class="service-card photo-card" data-aos="fade-up" data-aos-delay="140">
                <img src="https://images.pexels.com/photos/28793137/pexels-photo-28793137.jpeg" alt="Photographer">
                <div class="card-label">PHOTOG</div>
            </div>
        </div>
    </section>

    <section class="testimonial-slide story-block">
        <section class="testimonial-section">
            <div class="testimonial-header" data-aos="fade-up">
                <span class="tag">REAL STORIES</span>
                <h2 class="main-title">LOVE TALES FROM OUR COUPLES</h2>
            </div>

            <div class="swiper testimonial-carousel" data-aos="fade-up" data-aos-delay="120">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="card-img">
                                <img src="https://images.pexels.com/photos/3014857/pexels-photo-3014857.jpeg?auto=compress&cs=tinysrgb&w=600&h=400&fit=crop" alt="Happy Couple">
                            </div>
                            <div class="card-content">
                                <p class="quote">ChapterTwo made our wedding planning so seamless. The budget tool was incredibly accurate and every detail was perfection.</p>
                                <h4 class="couple-name">Gerald &amp; Erica</h4>
                                <p class="wedding-info">The Ritz-Carlton | 2025</p>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="card-img">
                                <img src="assets/images/home/makeup.png" alt="Wedding Celebration">
                            </div>
                            <div class="card-content">
                                <p class="quote">Elegant design and professional coordination. Our dream wedding came to life exactly as we imagined, stress-free and beautiful.</p>
                                <h4 class="couple-name">Daniel &amp; Sherlyn</h4>
                                <p class="wedding-info">Garden Villa | 2026</p>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="card-img">
                                <img src="assets/images/home/malay-wedding.jpg" alt="Romantic Ceremony">
                            </div>
                            <div class="card-content">
                                <p class="quote">From venue selection to the smallest floral arrangement, everything was magical. Highly recommend their planning expertise.</p>
                                <h4 class="couple-name">Marcus &amp; Olivia</h4>
                                <p class="wedding-info">Coastal Estate | 2025</p>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="card-img">
                                <img src="assets/images/home/cn-wedding.jpg" alt="Joyful Wedding">
                            </div>
                            <div class="card-content">
                                <p class="quote">The team understood our taste immediately and translated it into a day that felt deeply personal and elegant.</p>
                                <h4 class="couple-name">Noah &amp; Aria</h4>
                                <p class="wedding-info">Lakeside Manor | 2026</p>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="card-img">
                                <img src="assets/images/home/indian-wedding.jpg" alt="Joyful Wedding">
                            </div>
                            <div class="card-content">
                                <p class="quote">The team understood our taste immediately and translated it into a day that felt deeply personal and elegant.</p>
                                <h4 class="couple-name">Noah &amp; Aria</h4>
                                <p class="wedding-info">Lakeside Manor | 2026</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </section>
    </section>

   <section class="footer-cta">
    <div class="container">
        <h2 class="title">Ready to write your Chapter Two?</h2>
        <div class="contact-wrapper">
            <a href="<?php echo base_url('catalogue.php'); ?>" class="btn-contact">BOOK A WEDDING PLAN NOW</a>
        </div>
    </div>
</section>

</main>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/bundled/lenis.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/rellax@1.12.1/rellax.min.js"></script>
<script src="<?php echo base_url('assets/js/index-storyflow.js'); ?>"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
