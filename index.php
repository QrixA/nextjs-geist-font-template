<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get announcement if any
$announcement = getGlobalAnnouncement();

// Load language based on user preference or default
$lang = loadLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['language']) ? $_SESSION['language'] : DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Cloud Hosting Solutions</title>
    <meta name="description" content="High-performance cloud hosting solutions with data centers in Indonesia, Singapore, and Japan.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="landing-body">
    <header class="header">
        <nav class="nav">
            <div class="logo">
                <a href="index.php"><h1><?php echo SITE_NAME; ?></h1></a>
            </div>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="#datacenters">Data Centers</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>High-Performance Cloud Infrastructure</h1>
                <p>Deploy your applications with confidence using our reliable and scalable cloud hosting solutions.</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary">Start Free Trial</a>
                    <a href="#pricing" class="btn btn-secondary">View Pricing</a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">99.9%</span>
                        <span class="stat-label">Uptime</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">3</span>
                        <span class="stat-label">Data Centers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Support</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <h2>Why Choose Us</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>High Performance</h3>
                    <p>Enterprise-grade hardware with NVMe SSDs for lightning-fast performance.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h3>Advanced Security</h3>
                    <p>DDoS protection and regular security updates to keep your data safe.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h3>Auto Scaling</h3>
                    <p>Automatically scale resources based on your application's demands.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Pay As You Go</h3>
                    <p>Flexible pricing with hourly billing and no long-term commitments.</p>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="pricing">
            <h2>Simple, Transparent Pricing</h2>
            <div class="pricing-grid">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Starter</h3>
                        <div class="price">
                            <span class="amount">$5</span>
                            <span class="period">/month</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li>1 vCPU Core</li>
                        <li>1GB RAM</li>
                        <li>25GB NVMe Storage</li>
                        <li>1TB Bandwidth</li>
                        <li>DDoS Protection</li>
                    </ul>
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                </div>
                
                <div class="pricing-card popular">
                    <div class="popular-badge">Most Popular</div>
                    <div class="pricing-header">
                        <h3>Professional</h3>
                        <div class="price">
                            <span class="amount">$15</span>
                            <span class="period">/month</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li>2 vCPU Cores</li>
                        <li>4GB RAM</li>
                        <li>80GB NVMe Storage</li>
                        <li>3TB Bandwidth</li>
                        <li>DDoS Protection</li>
                        <li>Priority Support</li>
                    </ul>
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                </div>
                
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Enterprise</h3>
                        <div class="price">
                            <span class="amount">$30</span>
                            <span class="period">/month</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li>4 vCPU Cores</li>
                        <li>8GB RAM</li>
                        <li>160GB NVMe Storage</li>
                        <li>5TB Bandwidth</li>
                        <li>DDoS Protection</li>
                        <li>24/7 Priority Support</li>
                        <li>Dedicated IP</li>
                    </ul>
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                </div>
            </div>
        </section>

        <!-- Data Centers Section -->
        <section id="datacenters" class="datacenters">
            <h2>Global Data Center Locations</h2>
            <div class="datacenter-grid">
                <div class="datacenter-card">
                    <h3>Jakarta, Indonesia</h3>
                    <p>Strategic location for Southeast Asian markets with low-latency connectivity.</p>
                    <ul class="datacenter-specs">
                        <li>Tier-4 Facility</li>
                        <li>Redundant Power</li>
                        <li>24/7 Security</li>
                    </ul>
                </div>
                
                <div class="datacenter-card">
                    <h3>Singapore</h3>
                    <p>High-performance infrastructure hub with excellent global connectivity.</p>
                    <ul class="datacenter-specs">
                        <li>Tier-4 Facility</li>
                        <li>Green Energy</li>
                        <li>Advanced Cooling</li>
                    </ul>
                </div>
                
                <div class="datacenter-card">
                    <h3>Tokyo, Japan</h3>
                    <p>Ultra-low latency access to East Asian markets with reliable infrastructure.</p>
                    <ul class="datacenter-specs">
                        <li>Tier-4 Facility</li>
                        <li>Earthquake Protection</li>
                        <li>Multiple Carriers</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Company</h3>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Blog</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Products</h3>
                <ul>
                    <li><a href="#">Cloud Servers</a></li>
                    <li><a href="#">Managed Hosting</a></li>
                    <li><a href="#">Storage</a></li>
                    <li><a href="#">Backup</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Support</h3>
                <ul>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">API Reference</a></li>
                    <li><a href="#">Status</a></li>
                    <li><a href="#">Security</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Legal</h3>
                <ul>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">SLA</a></li>
                    <li><a href="#">GDPR</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <style>
    .landing-body {
        font-family: 'Inter', sans-serif;
        line-height: 1.6;
        color: #333;
    }

    /* Hero Section */
    .hero {
        background: linear-gradient(135deg, #000 0%, #333 100%);
        color: #fff;
        padding: 8rem 2rem;
        text-align: center;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .hero-content {
        max-width: 800px;
        margin: 0 auto;
    }

    .hero h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }

    .hero p {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        color: #ccc;
    }

    .hero-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-bottom: 3rem;
    }

    .hero-stats {
        display: flex;
        justify-content: center;
        gap: 4rem;
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        display: block;
        font-size: 2.5rem;
        font-weight: 700;
        color: #fff;
    }

    .stat-label {
        color: #ccc;
        font-size: 0.875rem;
    }

    /* Features Section */
    .features {
        padding: 6rem 2rem;
        background: #fff;
    }

    .features h2 {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 4rem;
        color: #000;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .feature-card {
        text-align: center;
        padding: 2rem;
    }

    .feature-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .feature-card h3 {
        color: #000;
        margin-bottom: 1rem;
    }

    .feature-card p {
        color: #666;
    }

    /* Pricing Section */
    .pricing {
        padding: 6rem 2rem;
        background: #f8f9fa;
    }

    .pricing h2 {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 4rem;
        color: #000;
    }

    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .pricing-card {
        background: #fff;
        border-radius: 10px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        position: relative;
    }

    .pricing-card.popular {
        transform: scale(1.05);
        border: 2px solid #000;
    }

    .popular-badge {
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: #000;
        color: #fff;
        padding: 0.25rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
    }

    .pricing-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .pricing-header h3 {
        color: #000;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .price {
        font-size: 2.5rem;
        color: #000;
    }

    .period {
        font-size: 1rem;
        color: #666;
    }

    .pricing-features {
        list-style: none;
        margin: 2rem 0;
        padding: 0;
    }

    .pricing-features li {
        padding: 0.5rem 0;
        color: #666;
    }

    .pricing-features li::before {
        content: "✓";
        color: #000;
        margin-right: 0.5rem;
    }

    /* Data Centers Section */
    .datacenters {
        padding: 6rem 2rem;
        background: #fff;
    }

    .datacenters h2 {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 4rem;
        color: #000;
    }

    .datacenter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .datacenter-card {
        padding: 2rem;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .datacenter-card h3 {
        color: #000;
        margin-bottom: 1rem;
    }

    .datacenter-specs {
        list-style: none;
        padding: 0;
        margin-top: 1.5rem;
    }

    .datacenter-specs li {
        padding: 0.5rem 0;
        color: #666;
    }

    .datacenter-specs li::before {
        content: "•";
        color: #000;
        margin-right: 0.5rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .hero h1 {
            font-size: 2.5rem;
        }
        
        .hero p {
            font-size: 1.125rem;
        }
        
        .hero-buttons {
            flex-direction: column;
        }
        
        .hero-stats {
            gap: 2rem;
        }
        
        .pricing-card.popular {
            transform: none;
        }
    }
    </style>
</body>
</html>
