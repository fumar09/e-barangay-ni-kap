<?php
/**
 * Services Page
 * e-Barangay ni Kap
 * Created: July 21, 2025
 */

$page_title = 'Barangay Services | Certificate Requests & Community Programs | e-Barangay ni Kap';
$page_description = 'Access professional barangay services including Barangay Clearance, Certificate of Indigency, Residency Certificate, and Business Permit with same-day processing. Modern e-governance platform for residents of San Joaquin, Palo, Leyte.';
$page_keywords = 'barangay services, certificate request, barangay clearance, indigency certificate, residency certificate, business permit, same day processing, palo leyte, e-governance';
$page_author = 'Barangay San Joaquin Administration';
$page_canonical = 'https://ebarangay-ni-kap.com/pages/services.php';

// Include header template
include '../templates/header.php';
?>

<!-- Services Hero Section -->
<section class="services-hero">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="text-white mb-4 animate-text">Barangay Services</h1>
                <p class="text-white lead mb-4 animate-text-delay">Professional services delivered with efficiency and care. Experience same-day processing for most of our services.</p>
                <div class="services-stats">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3>Same Day</h3>
                                <p>Processing</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h3>Secure</h3>
                                <p>Transactions</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3>Expert</h3>
                                <p>Staff</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Categories -->
<section class="services-section py-5">
    <div class="container">
        <!-- Certificate Services -->
        <div class="service-category mb-5">
            <div class="row align-items-center mb-4">
                <div class="col-12 text-center">
                    <h2 class="text-dark-navy mb-3">Certificate Services</h2>
                    <p class="text-medium-gray">Official documents and certifications processed with same-day service</p>
                    <div class="section-divider mx-auto mb-4"></div>
                </div>
            </div>
            
            <div class="row">
                <!-- Barangay Clearance -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-bs-toggle="tooltip" title="Click to request this service - Login required">
                        <div class="service-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h5>Barangay Clearance</h5>
                        <p class="service-description">Official clearance for employment, business, or other legal purposes.</p>
                        <div class="service-info">
                            <div class="processing-time">
                                <i class="fas fa-clock text-primary-blue"></i>
                                <span>Same Day Processing</span>
                            </div>
                            <div class="service-fee">
                                <i class="fas fa-peso-sign text-golden-yellow"></i>
                                <span>₱30.00</span>
                            </div>
                        </div>
                        <button class="btn btn-service" onclick="requireLogin('clearance')" data-bs-toggle="tooltip" title="Request Barangay Clearance">
                            <i class="fas fa-file-alt me-2"></i>Request Service
                        </button>
                    </div>
                </div>

                <!-- Certificate of Indigency -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-bs-toggle="tooltip" title="Click to request this service - Login required">
                        <div class="service-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h5>Certificate of Indigency</h5>
                        <p class="service-description">Certification for low-income families to access government assistance programs.</p>
                        <div class="service-info">
                            <div class="processing-time">
                                <i class="fas fa-clock text-primary-blue"></i>
                                <span>Same Day Processing</span>
                            </div>
                            <div class="service-fee">
                                <i class="fas fa-peso-sign text-golden-yellow"></i>
                                <span>Free</span>
                            </div>
                        </div>
                        <button class="btn btn-service" onclick="requireLogin('indigency')" data-bs-toggle="tooltip" title="Request Certificate of Indigency">
                            <i class="fas fa-file-alt me-2"></i>Request Service
                        </button>
                    </div>
                </div>

                <!-- Certificate of Residency -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-bs-toggle="tooltip" title="Click to request this service - Login required">
                        <div class="service-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h5>Certificate of Residency</h5>
                        <p class="service-description">Proof of residence within the barangay for various official purposes.</p>
                        <div class="service-info">
                            <div class="processing-time">
                                <i class="fas fa-clock text-primary-blue"></i>
                                <span>Same Day Processing</span>
                            </div>
                            <div class="service-fee">
                                <i class="fas fa-peso-sign text-golden-yellow"></i>
                                <span>₱25.00</span>
                            </div>
                        </div>
                        <button class="btn btn-service" onclick="requireLogin('residency')" data-bs-toggle="tooltip" title="Request Certificate of Residency">
                            <i class="fas fa-file-alt me-2"></i>Request Service
                        </button>
                    </div>
                </div>

                <!-- Business Permit -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-bs-toggle="tooltip" title="Click to request this service - Login required">
                        <div class="service-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h5>Business Permit</h5>
                        <p class="service-description">Permit for small-scale businesses operating within the barangay.</p>
                        <div class="service-info">
                            <div class="processing-time">
                                <i class="fas fa-clock text-primary-blue"></i>
                                <span>Same Day Processing</span>
                            </div>
                            <div class="service-fee">
                                <i class="fas fa-peso-sign text-golden-yellow"></i>
                                <span>₱50.00</span>
                            </div>
                        </div>
                        <button class="btn btn-service" onclick="requireLogin('business')" data-bs-toggle="tooltip" title="Request Business Permit">
                            <i class="fas fa-file-alt me-2"></i>Request Service
                        </button>
                    </div>
                </div>

                <!-- Good Moral Certificate -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-bs-toggle="tooltip" title="Click to request this service - Login required">
                        <div class="service-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h5>Good Moral Certificate</h5>
                        <p class="service-description">Character reference certificate for students and professionals.</p>
                        <div class="service-info">
                            <div class="processing-time">
                                <i class="fas fa-clock text-primary-blue"></i>
                                <span>Same Day Processing</span>
                            </div>
                            <div class="service-fee">
                                <i class="fas fa-peso-sign text-golden-yellow"></i>
                                <span>₱20.00</span>
                            </div>
                        </div>
                        <button class="btn btn-service" onclick="requireLogin('good-moral')" data-bs-toggle="tooltip" title="Request Good Moral Certificate">
                            <i class="fas fa-file-alt me-2"></i>Request Service
                        </button>
                    </div>
                </div>

                <!-- First Time Job Seeker Certificate -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-bs-toggle="tooltip" title="Click to request this service - Login required">
                        <div class="service-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h5>First Time Job Seeker</h5>
                        <p class="service-description">Certificate for first-time job seekers to avail tax exemptions.</p>
                        <div class="service-info">
                            <div class="processing-time">
                                <i class="fas fa-clock text-primary-blue"></i>
                                <span>Same Day Processing</span>
                            </div>
                            <div class="service-fee">
                                <i class="fas fa-peso-sign text-golden-yellow"></i>
                                <span>Free</span>
                            </div>
                        </div>
                        <button class="btn btn-service" onclick="requireLogin('job-seeker')" data-bs-toggle="tooltip" title="Request First Time Job Seeker Certificate">
                            <i class="fas fa-file-alt me-2"></i>Request Service
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Community Services -->
        <div class="service-category mb-5">
            <div class="row align-items-center mb-4">
                <div class="col-12 text-center">
                    <h2 class="text-dark-navy mb-3">Community Services</h2>
                    <p class="text-medium-gray">Programs and services for community development and welfare</p>
                    <div class="section-divider mx-auto mb-4"></div>
                </div>
            </div>
            
            <div class="row">
                <!-- Health Services -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-bs-toggle="tooltip" title="Click to access this service - Login required">
                        <div class="service-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h5>Health Services</h5>
                        <p class="service-description">Basic healthcare, immunization, and health consultations.</p>
                        <div class="service-info">
                            <div class="processing-time">
                                <i class="fas fa-clock text-primary-blue"></i>
                                <span>Walk-in Basis</span>
                            </div>
                            <div class="service-fee">
                                <i class="fas fa-peso-sign text-golden-yellow"></i>
                                <span>Free</span>
                            </div>
                        </div>
                        <button class="btn btn-service" onclick="requireLogin('health')" data-bs-toggle="tooltip" title="Access Health Services">
                            <i class="fas fa-stethoscope me-2"></i>Access Service
                        </button>
                    </div>
                </div>

                <!-- Blotter Reporting -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-bs-toggle="tooltip" title="Click to file a report - Login required">
                        <div class="service-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h5>Blotter Reporting</h5>
                        <p class="service-description">File incident reports and complaints for peace and order matters.</p>
                        <div class="service-info">
                            <div class="processing-time">
                                <i class="fas fa-clock text-primary-blue"></i>
                                <span>Immediate</span>
                            </div>
                            <div class="service-fee">
                                <i class="fas fa-peso-sign text-golden-yellow"></i>
                                <span>Free</span>
                            </div>
                        </div>
                        <button class="btn btn-service" onclick="requireLogin('blotter')" data-bs-toggle="tooltip" title="File Blotter Report">
                            <i class="fas fa-file-signature me-2"></i>File Report
                        </button>
                    </div>
                </div>

                <!-- Disaster Assistance -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-bs-toggle="tooltip" title="Click to request assistance - Login required">
                        <div class="service-icon">
                            <i class="fas fa-life-ring"></i>
                        </div>
                        <h5>Disaster Assistance</h5>
                        <p class="service-description">Emergency assistance and relief goods during disasters and calamities.</p>
                        <div class="service-info">
                            <div class="processing-time">
                                <i class="fas fa-clock text-primary-blue"></i>
                                <span>Emergency Response</span>
                            </div>
                            <div class="service-fee">
                                <i class="fas fa-peso-sign text-golden-yellow"></i>
                                <span>Free</span>
                            </div>
                        </div>
                        <button class="btn btn-service" onclick="requireLogin('disaster')" data-bs-toggle="tooltip" title="Request Disaster Assistance">
                            <i class="fas fa-hands-helping me-2"></i>Request Help
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requirements Section -->
        <div class="requirements-section">
            <div class="row">
                <div class="col-12 text-center mb-4">
                    <h2 class="text-dark-navy mb-3">Service Requirements</h2>
                    <p class="text-medium-gray">General requirements for availing barangay services</p>
                    <div class="section-divider mx-auto mb-4"></div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="requirements-card">
                        <h5><i class="fas fa-id-card me-2 text-primary-blue"></i>General Requirements</h5>
                        <ul class="requirements-list">
                            <li><i class="fas fa-check text-success me-2"></i>Valid Government ID</li>
                            <li><i class="fas fa-check text-success me-2"></i>Proof of Residency</li>
                            <li><i class="fas fa-check text-success me-2"></i>Accomplished Request Form</li>
                            <li><i class="fas fa-check text-success me-2"></i>Payment of Required Fees</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="requirements-card">
                        <h5><i class="fas fa-clock me-2 text-primary-blue"></i>Service Hours</h5>
                        <div class="service-hours">
                            <div class="hour-item">
                                <strong>Monday - Friday:</strong> 8:00 AM - 5:00 PM
                            </div>
                            <div class="hour-item">
                                <strong>Saturday:</strong> 8:00 AM - 12:00 PM
                            </div>
                            <div class="hour-item">
                                <strong>Sunday:</strong> Closed
                            </div>
                            <div class="hour-item emergency">
                                <strong>Emergency Services:</strong> 24/7
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="services-cta">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h2 class="text-white mb-3">Access Barangay Services</h2>
                <p class="text-white lead mb-4">Sign in to your account to request certificates and access our community services.</p>
                <a href="<?php echo APP_URL; ?>/auth/login.php" class="btn btn-cta" data-bs-toggle="tooltip" title="Sign in to access services">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In to Continue
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// Function to require login for service requests
function requireLogin(serviceType) {
    // Show login requirement message
    if (confirm('You need to login first to request this service. Would you like to go to the login page?')) {
        window.location.href = '<?php echo APP_URL; ?>/auth/login.php?redirect=services&service=' + serviceType;
    }
}

// Add smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// Add animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
        }
    });
}, observerOptions);

// Observe all service cards and requirements cards
document.querySelectorAll('.service-card, .requirements-card').forEach(card => {
    observer.observe(card);
});
</script>

<!-- Structured Data for SEO -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "Barangay Services",
    "description": "Access professional barangay services including Barangay Clearance, Certificate of Indigency, Residency Certificate, and Business Permit with same-day processing.",
    "url": "https://ebarangay-ni-kap.com/pages/services.php",
    "mainEntity": {
        "@type": "Service",
        "name": "Barangay Services",
        "description": "Comprehensive barangay services for residents of San Joaquin, Palo, Leyte",
        "provider": {
            "@type": "GovernmentOrganization",
            "name": "Barangay San Joaquin",
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "Barangay San Joaquin",
                "addressLocality": "Palo",
                "addressRegion": "Leyte",
                "addressCountry": "PH"
            }
        },
        "hasOfferCatalog": {
            "@type": "OfferCatalog",
            "name": "Barangay Services",
            "itemListElement": [
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "Barangay Clearance",
                        "description": "Official clearance for employment, business, or other legal purposes",
                        "offeredBy": {
                            "@type": "GovernmentOrganization",
                            "name": "Barangay San Joaquin"
                        }
                    },
                    "price": "30.00",
                    "priceCurrency": "PHP"
                },
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "Certificate of Indigency",
                        "description": "Certificate for indigent families",
                        "offeredBy": {
                            "@type": "GovernmentOrganization",
                            "name": "Barangay San Joaquin"
                        }
                    },
                    "price": "0.00",
                    "priceCurrency": "PHP"
                },
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "Certificate of Residency",
                        "description": "Certificate confirming residency in the barangay",
                        "offeredBy": {
                            "@type": "GovernmentOrganization",
                            "name": "Barangay San Joaquin"
                        }
                    },
                    "price": "25.00",
                    "priceCurrency": "PHP"
                },
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "Business Permit",
                        "description": "Permit for operating businesses in the barangay",
                        "offeredBy": {
                            "@type": "GovernmentOrganization",
                            "name": "Barangay San Joaquin"
                        }
                    },
                    "price": "50.00",
                    "priceCurrency": "PHP"
                }
            ]
        }
    },
    "breadcrumb": {
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "https://ebarangay-ni-kap.com/"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Services",
                "item": "https://ebarangay-ni-kap.com/pages/services.php"
            }
        ]
    }
}
</script>

<?php
// Include footer template
include '../templates/footer.php';
?> 