<?php
require_once __DIR__ . '/../includes/config/constants.php';
require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/functions/helpers.php';

$auth = new Auth();
$auth->requireAuth();
$user = $auth->getCurrentUser();
if ($user['role'] !== 'purok_leader') {
    header('Location: ../auth/login.php');
    exit;
}
$purok_id = $user['purok_id'] ?? null;
$stats = getDashboardStats('purok', ['purok_id' => $purok_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purok Leader Dashboard | e-Barangay ni Kap</title>
    <meta name="description" content="Purok Leader dashboard for e-Barangay ni Kap. View residents, submit reports, track statistics, and more.">
    <meta name="keywords" content="purok leader dashboard, barangay purok, e-barangay">
    <link rel="icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/custom.js"></script>
</head>
<body class="purok-dashboard">
    <!-- Top Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <button class="sidebar-toggle btn btn-link" id="sidebarToggle" data-bs-toggle="tooltip" title="Toggle Sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand ms-2" href="#">
                <img src="../assets/images/sjlg.png" alt="e-Barangay Logo" height="40">
            </a>
            <span class="ms-auto fw-bold text-dark-navy">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
        </div>
    </nav>
    <!-- Sidebar -->
    <aside class="sidebar bg-primary-blue" id="purokSidebar">
        <div class="sidebar-header p-3 text-center">
            <img src="../assets/images/sjlg.png" alt="Logo" class="img-fluid mb-2" style="height:48px;">
            <h5 class="text-white mb-0">Purok Leader</h5>
        </div>
        <nav class="sidebar-nav nav flex-column px-2">
            <a class="nav-link text-white" href="#" data-bs-toggle="tooltip" title="Dashboard"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            <a class="nav-link text-white" href="../purok/residents.php" data-bs-toggle="tooltip" title="View Residents"><i class="fas fa-users me-2"></i> Residents</a>
            <a class="nav-link text-white" href="../purok/reports.php" data-bs-toggle="tooltip" title="Submit Reports"><i class="fas fa-file-alt me-2"></i> Reports</a>
            <a class="nav-link text-white" href="../purok/announcements.php" data-bs-toggle="tooltip" title="Announcements"><i class="fas fa-bullhorn me-2"></i> Announcements</a>
            <a class="nav-link text-white" href="../purok/events.php" data-bs-toggle="tooltip" title="Events"><i class="fas fa-calendar-alt me-2"></i> Events</a>
            <a class="nav-link text-white" href="../purok/statistics.php" data-bs-toggle="tooltip" title="Statistics"><i class="fas fa-chart-bar me-2"></i> Statistics</a>
            <a class="nav-link text-white" href="../purok/feedback.php" data-bs-toggle="tooltip" title="Submit Feedback"><i class="fas fa-comment-dots me-2"></i> Feedback</a>
            <a class="nav-link text-white" href="../purok/certificates.php" data-bs-toggle="tooltip" title="Certificate Requests"><i class="fas fa-file-signature me-2"></i> Certificates</a>
            <a class="nav-link text-danger mt-2" href="../auth/logout.php" data-bs-toggle="tooltip" title="Logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </aside>
    <!-- Main Content -->
    <main class="main-wrapper">
        <div class="container-fluid py-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card stat-card modern-card shadow-sm" data-bs-toggle="tooltip" title="Total Residents in Purok">
                        <div class="card-body text-center">
                            <div class="stat-icon-wrapper mb-2"><i class="fas fa-user-friends fa-2x text-info-blue-500"></i></div>
                            <div class="stat-number fs-2 fw-bold"><?php echo $stats['total_residents']; ?></div>
                            <div class="stat-label">Residents</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card modern-card shadow-sm" data-bs-toggle="tooltip" title="Households">
                        <div class="card-body text-center">
                            <div class="stat-icon-wrapper mb-2"><i class="fas fa-home fa-2x text-golden-yellow"></i></div>
                            <div class="stat-number fs-2 fw-bold"><?php echo $stats['households']; ?></div>
                            <div class="stat-label">Households</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card modern-card shadow-sm" data-bs-toggle="tooltip" title="Total Certificate Requests">
                        <div class="card-body text-center">
                            <div class="stat-icon-wrapper mb-2"><i class="fas fa-file-alt fa-2x text-warning"></i></div>
                            <div class="stat-number fs-2 fw-bold"><?php echo $stats['total_requests']; ?></div>
                            <div class="stat-label">Requests</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card modern-card shadow-sm" data-bs-toggle="tooltip" title="Feedback Submitted">
                        <div class="card-body text-center">
                            <div class="stat-icon-wrapper mb-2"><i class="fas fa-comment-dots fa-2x text-success"></i></div>
                            <div class="stat-number fs-2 fw-bold"><?php echo $stats['feedback_count']; ?></div>
                            <div class="stat-label">Feedback</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card modern-card shadow-sm">
                        <div class="card-header-modern d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Purok Overview</h5>
                        </div>
                        <div class="card-body-modern">
                            <p class="mb-0">View residents, submit reports, track statistics, and more for your assigned purok. Use the sidebar to navigate between modules. All data is live and up-to-date.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Footer -->
    <footer class="footer bg-dark-navy text-white py-3 mt-auto">
        <div class="container-fluid text-center">
            <span>&copy; <?php echo date('Y'); ?> e-Barangay ni Kap. All rights reserved. | Purok Leader Dashboard</span>
        </div>
    </footer>
    <script>
    // Sidebar toggle
    $(document).ready(function() {
        $('#sidebarToggle').on('click', function() {
            $('#purokSidebar').toggleClass('show');
        });
    });
    </script>
</body>
</html>