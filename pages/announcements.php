<?php
/**
 * Announcements Page
 * e-Barangay ni Kap
 * Created: July 21, 2025
 */

require_once '../includes/config/constants.php';
require_once '../includes/config/database.php';
require_once '../includes/functions/helpers.php';

$page_title = 'Community Announcements | Latest News & Updates | e-Barangay ni Kap';
$page_description = 'Stay updated with the latest announcements, news, events, and important updates from Barangay San Joaquin, Palo, Leyte. Get real-time community information and official notices.';
$page_keywords = 'barangay announcements, community news, palo leyte updates, barangay san joaquin news, local government announcements, community events';
$page_author = 'Barangay San Joaquin Administration';
$page_canonical = 'https://ebarangay-ni-kap.com/pages/announcements.php';

// Get database connection
$db = getDB();

// Fetch announcements from database (with fallback sample data)
try {
    $announcements = $db->fetchAll(
        "SELECT * FROM announcements 
         WHERE is_published = 1 
         ORDER BY created_at DESC 
         LIMIT 20"
    );
} catch (Exception $e) {
    // Fallback sample data if database not available
    $announcements = [
        [
            'id' => 1,
            'title' => 'New Online Certificate Request System',
            'content' => 'We are pleased to announce the launch of our new online certificate request system. Residents can now request barangay clearances, certificates of indigency, and other documents through our web portal. This system provides convenience and faster processing times for all residents.',
            'created_at' => '2025-07-20',
            'author_name' => 'Barangay Administration',
            'category' => 'System Update',
            'is_featured' => 1
        ],
        [
            'id' => 2,
            'title' => 'Community Clean-Up Drive - July 28, 2025',
            'content' => 'Join us for our monthly community clean-up drive on July 28, 2025, starting at 6:00 AM. Meeting point is at the Barangay Hall. Please bring your own cleaning materials. Breakfast will be provided to all participants. Let us work together to keep our barangay clean and beautiful.',
            'created_at' => '2025-07-19',
            'author_name' => 'Environmental Committee',
            'category' => 'Community Event',
            'is_featured' => 1
        ],
        [
            'id' => 3,
            'title' => 'Vaccination Schedule - Second Dose COVID-19',
            'content' => 'Second dose COVID-19 vaccination will be available on July 25, 2025, from 8:00 AM to 4:00 PM at the Barangay Health Center. Please bring your vaccination card. Walk-in appointments are available, but priority will be given to scheduled appointments.',
            'created_at' => '2025-07-18',
            'author_name' => 'Health Committee',
            'category' => 'Health Advisory',
            'is_featured' => 0
        ],
        [
            'id' => 4,
            'title' => 'Barangay Assembly Meeting - August 5, 2025',
            'content' => 'All residents are invited to attend the quarterly barangay assembly meeting on August 5, 2025, at 2:00 PM at the Barangay Hall. Important community matters will be discussed including budget allocation, upcoming projects, and community concerns.',
            'created_at' => '2025-07-17',
            'author_name' => 'Barangay Council',
            'category' => 'Official Notice',
            'is_featured' => 0
        ],
        [
            'id' => 5,
            'title' => 'Senior Citizens Pension Distribution',
            'content' => 'Senior citizens pension distribution will be held on July 30, 2025, from 9:00 AM to 3:00 PM at the Barangay Hall. Please bring your valid IDs and pension booklets. Family representatives must present authorization letters.',
            'created_at' => '2025-07-16',
            'author_name' => 'Social Services',
            'category' => 'Benefits Distribution',
            'is_featured' => 0
        ],
        [
            'id' => 6,
            'title' => 'Street Lighting Maintenance Schedule',
            'content' => 'Street lighting maintenance will be conducted from July 22-24, 2025. Some areas may experience temporary power interruptions during evening hours. We apologize for any inconvenience caused.',
            'created_at' => '2025-07-15',
            'author_name' => 'Public Works',
            'category' => 'Maintenance Notice',
            'is_featured' => 0
        ]
    ];
}

// Include header template
include '../templates/header.php';
?>

<!-- Announcements Hero Section -->
<section class="announcements-hero">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="text-white mb-4 animate-text">Community Announcements</h1>
                <p class="text-white lead mb-4 animate-text-delay">Stay informed with the latest updates, events, and important notices from Barangay San Joaquin.</p>
                <div class="announcement-stats">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <h3><?php echo count($announcements); ?></h3>
                                <p>Active Announcements</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h3>Daily</h3>
                                <p>Updates</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3>Community</h3>
                                <p>Focused</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Announcements -->
<?php 
$featured = array_filter($announcements, function($announcement) {
    return isset($announcement['is_featured']) && $announcement['is_featured'] == 1;
});
if (!empty($featured)): 
?>
<section class="featured-announcements py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h2 class="text-dark-navy mb-3">Featured Announcements</h2>
                <p class="text-medium-gray">Important updates you shouldn't miss</p>
                <div class="section-divider mx-auto mb-4"></div>
            </div>
        </div>
        
        <div class="row">
            <?php foreach (array_slice($featured, 0, 2) as $announcement): ?>
            <div class="col-lg-6 mb-4">
                <div class="featured-announcement-card" data-bs-toggle="tooltip" title="Click to read full announcement">
                    <div class="announcement-badge">
                        <i class="fas fa-star"></i> Featured
                    </div>
                    <div class="announcement-category">
                        <span class="category-badge"><?php echo htmlspecialchars($announcement['category']); ?></span>
                    </div>
                    <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                    <p class="announcement-excerpt"><?php echo htmlspecialchars(substr($announcement['content'], 0, 200)); ?>...</p>
                    <div class="announcement-meta">
                        <div class="announcement-date">
                            <i class="fas fa-calendar me-2"></i>
                            <?php echo formatDate($announcement['created_at'], 'F j, Y'); ?>
                        </div>
                        <div class="announcement-author">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($announcement['author_name'] ?? 'Barangay Administration'); ?>
                        </div>
                    </div>
                    <button class="btn btn-read-more" onclick="showAnnouncementModal(<?php echo $announcement['id']; ?>)" data-bs-toggle="tooltip" title="Read full announcement">
                        <i class="fas fa-arrow-right me-2"></i>Read More
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- All Announcements -->
<section class="all-announcements py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h2 class="text-dark-navy mb-3">All Announcements</h2>
                <p class="text-medium-gray">Complete list of community updates and notices</p>
                <div class="section-divider mx-auto mb-4"></div>
            </div>
        </div>

        <!-- Filter Options -->
        <div class="row mb-4">
            <div class="col-lg-6 offset-lg-3">
                <div class="announcement-filters">
                    <select class="form-select" id="categoryFilter" data-bs-toggle="tooltip" title="Filter announcements by category">
                        <option value="">All Categories</option>
                        <option value="System Update">System Updates</option>
                        <option value="Community Event">Community Events</option>
                        <option value="Health Advisory">Health Advisories</option>
                        <option value="Official Notice">Official Notices</option>
                        <option value="Benefits Distribution">Benefits Distribution</option>
                        <option value="Maintenance Notice">Maintenance Notices</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="row" id="announcementsList">
            <?php foreach ($announcements as $announcement): ?>
            <div class="col-lg-6 mb-4 announcement-item" data-category="<?php echo htmlspecialchars($announcement['category']); ?>">
                <div class="announcement-card" data-bs-toggle="tooltip" title="Click to read full announcement">
                    <div class="announcement-header">
                        <div class="announcement-category">
                            <span class="category-badge <?php echo strtolower(str_replace(' ', '-', $announcement['category'])); ?>">
                                <?php echo htmlspecialchars($announcement['category']); ?>
                            </span>
                        </div>
                        <div class="announcement-date">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo formatDate($announcement['created_at'], 'M j, Y'); ?>
                        </div>
                    </div>
                    
                    <div class="announcement-body">
                        <h5><?php echo htmlspecialchars($announcement['title']); ?></h5>
                        <p class="announcement-excerpt"><?php echo htmlspecialchars(substr($announcement['content'], 0, 150)); ?>...</p>
                    </div>
                    
                    <div class="announcement-footer">
                        <div class="announcement-author">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($announcement['author_name'] ?? 'Barangay Administration'); ?>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" onclick="showAnnouncementModal(<?php echo $announcement['id']; ?>)" data-bs-toggle="tooltip" title="Read full announcement">
                            <i class="fas fa-eye me-1"></i>Read More
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Announcement Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="announcementModalLabel">Announcement Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" data-bs-toggle="tooltip" title="Close announcement"></button>
            </div>
            <div class="modal-body" id="announcementModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" title="Close announcement">
                    <i class="fas fa-times me-2"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Subscribe Section -->
<section class="announcement-subscribe">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h2 class="text-white mb-3">Stay Connected</h2>
                <p class="text-white lead mb-4">Sign in to your account to receive personalized updates and stay informed about community matters.</p>
                <a href="<?php echo APP_URL; ?>/auth/login.php" class="btn btn-cta" data-bs-toggle="tooltip" title="Sign in to receive updates">
                    <i class="fas fa-bell me-2"></i>Sign In for Updates
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// Store announcements data for modal
const announcements = <?php echo json_encode($announcements); ?>;

// Show announcement modal
function showAnnouncementModal(id) {
    const announcement = announcements.find(a => a.id == id);
    if (announcement) {
        document.getElementById('announcementModalLabel').textContent = announcement.title;
        document.getElementById('announcementModalBody').innerHTML = `
            <div class="announcement-modal-content">
                <div class="announcement-meta mb-3">
                    <span class="badge bg-primary me-2">${announcement.category}</span>
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>${new Date(announcement.created_at).toLocaleDateString('en-US', { 
                            year: 'numeric', month: 'long', day: 'numeric' 
                        })}
                        <i class="fas fa-user ms-3 me-1"></i>${announcement.author_name || 'Barangay Administration'}
                    </small>
                </div>
                <div class="announcement-content">
                    ${announcement.content.replace(/\n/g, '<br>')}
                </div>
            </div>
        `;
        const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
        modal.show();
    }
}

// Filter announcements by category
document.getElementById('categoryFilter').addEventListener('change', function() {
    const selectedCategory = this.value;
    const announcements = document.querySelectorAll('.announcement-item');
    
    announcements.forEach(announcement => {
        const category = announcement.getAttribute('data-category');
        if (selectedCategory === '' || category === selectedCategory) {
            announcement.style.display = 'block';
        } else {
            announcement.style.display = 'none';
        }
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

// Observe all announcement cards
document.querySelectorAll('.announcement-card, .featured-announcement-card').forEach(card => {
    observer.observe(card);
});
</script>

<!-- Structured Data for SEO -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "Community Announcements",
    "description": "Stay updated with the latest announcements, news, events, and important updates from Barangay San Joaquin, Palo, Leyte.",
    "url": "https://ebarangay-ni-kap.com/pages/announcements.php",
    "mainEntity": {
        "@type": "ItemList",
        "name": "Community Announcements",
        "description": "Latest announcements and news from Barangay San Joaquin",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "item": {
                    "@type": "NewsArticle",
                    "headline": "New Online Certificate Request System",
                    "description": "Launch of our new online certificate request system for residents",
                    "author": {
                        "@type": "Organization",
                        "name": "Barangay San Joaquin Administration"
                    },
                    "publisher": {
                        "@type": "Organization",
                        "name": "Barangay San Joaquin"
                    }
                }
            },
            {
                "@type": "ListItem",
                "position": 2,
                "item": {
                    "@type": "Event",
                    "name": "Community Clean-Up Drive",
                    "description": "Monthly community clean-up drive for residents",
                    "organizer": {
                        "@type": "Organization",
                        "name": "Environmental Committee"
                    }
                }
            }
        ]
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
                "name": "Announcements",
                "item": "https://ebarangay-ni-kap.com/pages/announcements.php"
            }
        ]
    }
}
</script>

<?php
// Include footer template
include '../templates/footer.php';
?> 