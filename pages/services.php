<?php
// services.php

$page_title = "Our Services - GreenLife Wellness Center";
include '../includes/header.php';

// Fetch services from the database
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY name");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $services = [];
    $error_message = "Unable to load services at this time.";
}
?>

<main>
    <!-- Services Hero Section -->
    <section class="services-hero">
        <div class="container">
            <h1>Our Wellness Services</h1>
            <p class="hero-subtitle">Comprehensive health and wellness solutions tailored to your needs</p>
        </div>
    </section>

    <!-- Services Grid Section -->
    <section class="services-section">
        <div class="container">
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <div class="services-grid">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <div class="service-icon">
                                <?php
                                // Display different icons based on service name
                                $service_name = strtolower($service['name']);
                                if (strpos($service_name, 'massage') !== false) {
                                    echo '💆‍♀️';
                                } elseif (strpos($service_name, 'yoga') !== false) {
                                    echo '🧘‍♀️';
                                } elseif (strpos($service_name, 'nutrition') !== false) {
                                    echo '🥗';
                                } elseif (strpos($service_name, 'physio') !== false) {
                                    echo '🏃‍♂️';
                                } else {
                                    echo '🌿';
                                }
                                ?>
                            </div>
                            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p class="service-description">
                                <?php echo htmlspecialchars($service['description']); ?>
                            </p>
                            <div class="service-details">
                                <div class="service-duration">
                                    <strong>Duration:</strong> <?php echo htmlspecialchars($service['duration']); ?> minutes
                                </div>
                                <div class="service-price">
                                    $<?php echo number_format($service['price'], 2); ?>
                                </div>
                            </div>
                            <div class="service-actions">
                                <?php if (isLoggedIn()): ?>
                                    <a href="appointments.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">Book Now</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">Login to Book</a>
                                <?php endif; ?>
                                <a href="service-details.php?id=<?php echo $service['id']; ?>" class="btn btn-secondary">Learn More</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default services if database is empty -->
                    <div class="service-card">
                        <div class="service-icon">💆‍♀️</div>
                        <h3>Massage Therapy</h3>
                        <p class="service-description">Relaxing therapeutic massages to relieve stress and muscle tension.</p>
                        <div class="service-details">
                            <div class="service-duration"><strong>Duration:</strong> 60 minutes</div>
                            <div class="service-price">$80.00</div>
                        </div>
                        <div class="service-actions">
                            <?php if (isLoggedIn()): ?>
                                <a href="appointments.php" class="btn btn-primary">Book Now</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Login to Book</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="service-card">
                        <div class="service-icon">🧘‍♀️</div>
                        <h3>Yoga Classes</h3>
                        <p class="service-description">Personal and group yoga sessions for flexibility and mindfulness.</p>
                        <div class="service-details">
                            <div class="service-duration"><strong>Duration:</strong> 45 minutes</div>
                            <div class="service-price">$50.00</div>
                        </div>
                        <div class="service-actions">
                            <?php if (isLoggedIn()): ?>
                                <a href="appointments.php" class="btn btn-primary">Book Now</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Login to Book</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="service-card">
                        <div class="service-icon">🥗</div>
                        <h3>Nutrition Counseling</h3>
                        <p class="service-description">Personalized nutrition plans for optimal health and wellness.</p>
                        <div class="service-details">
                            <div class="service-duration"><strong>Duration:</strong> 30 minutes</div>
                            <div class="service-price">$40.00</div>
                        </div>
                        <div class="service-actions">
                            <?php if (isLoggedIn()): ?>
                                <a href="appointments.php" class="btn btn-primary">Book Now</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Login to Book</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="service-card">
                        <div class="service-icon">🏃‍♂️</div>
                        <h3>Physiotherapy</h3>
                        <p class="service-description">Professional rehabilitation and physical therapy services.</p>
                        <div class="service-details">
                            <div class="service-duration"><strong>Duration:</strong> 60 minutes</div>
                            <div class="service-price">$90.00</div>
                        </div>
                        <div class="service-actions">
                            <?php if (isLoggedIn()): ?>
                                <a href="appointments.php" class="btn btn-primary">Book Now</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Login to Book</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="services-cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Begin Your Wellness Journey?</h2>
                <p>Choose from our comprehensive range of services designed to improve your health and well-being.</p>
                <?php if (isLoggedIn()): ?>
                    <a href="appointments.php" class="btn btn-primary">Book Your Appointment</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary">Get Started Today</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>