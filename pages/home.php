<?php
$page_title = "Home - GreenLife Wellness Center";
include '../includes/header.php';
?>

<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to GreenLife Wellness Center</h1>
            <p>Your journey to optimal health and wellness starts here. Discover our holistic approach to mind, body, and spirit wellness.</p>
            <div class="hero-buttons">
                <a href="services.php" class="btn btn-primary">Explore Services</a>
                <a href="appointments.php" class="btn btn-secondary">Book Appointment</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Why Choose GreenLife?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🏥</div>
                    <h3>Expert Care</h3>
                    <p>Our certified professionals provide personalized care tailored to your unique wellness needs.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌿</div>
                    <h3>Holistic Approach</h3>
                    <p>We focus on treating the whole person - mind, body, and spirit - for complete wellness.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📅</div>
                    <h3>Flexible Scheduling</h3>
                    <p>Easy online booking with flexible appointment times to fit your busy lifestyle.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💚</div>
                    <h3>Natural Solutions</h3>
                    <p>We emphasize natural, sustainable approaches to health and wellness.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Preview Section -->
    <section class="services-preview">
        <div class="container">
            <h2 class="section-title">Our Popular Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">💆‍♀️</div>
                    <h3>Massage Therapy</h3>
                    <p>Relaxing therapeutic massages to relieve stress and muscle tension.</p>
                    <div class="service-price">From $80</div>
                </div>
                <div class="service-card">
                    <div class="service-icon">🧘‍♀️</div>
                    <h3>Yoga Classes</h3>
                    <p>Personal and group yoga sessions for flexibility and mindfulness.</p>
                    <div class="service-price">From $50</div>
                </div>
                <div class="service-card">
                    <div class="service-icon">🥗</div>
                    <h3>Nutrition Counseling</h3>
                    <p>Personalized nutrition plans for optimal health and wellness.</p>
                    <div class="service-price">From $40</div>
                </div>
                <div class="service-card">
                    <div class="service-icon">🏃‍♂️</div>
                    <h3>Physiotherapy</h3>
                    <p>Professional rehabilitation and physical therapy services.</p>
                    <div class="service-price">From $90</div>
                </div>
            </div>
            <div class="text-center">
                <a href="services.php" class="btn btn-primary">View All Services</a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="container">
            <h2 class="section-title">What Our Clients Say</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"GreenLife has transformed my approach to wellness. The staff is incredibly knowledgeable and caring."</p>
                    </div>
                    <div class="testimonial-author">
                        <strong>Sarah Johnson</strong>
                        <span>Regular Client</span>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"The holistic approach here really works. I feel better than I have in years!"</p>
                    </div>
                    <div class="testimonial-author">
                        <strong>Michael Chen</strong>
                        <span>Physiotherapy Client</span>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"Booking appointments is so easy, and the therapists are amazing. Highly recommended!"</p>
                    </div>
                    <div class="testimonial-author">
                        <strong>Emily Rodriguez</strong>
                        <span>Massage Therapy Client</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Your Wellness Journey?</h2>
                <p>Join thousands of satisfied clients who have transformed their lives with our expert care.</p>
                <div class="cta-buttons">
                    <?php if (isLoggedIn()): ?>
                        <a href="appointments.php" class="btn btn-primary">Book Your Appointment</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Get Started Today</a>
                        <a href="login.php" class="btn btn-secondary">Already a Member?</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>