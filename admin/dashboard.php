<?php
require_once __DIR__ . '/../includes/config/constants.php';
require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/functions/helpers.php';

$auth = new Auth();
$auth->requireAuth();
$user = $auth->getCurrentUser();
if ($user['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
$stats = getDashboardStats('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | e-Barangay ni Kap</title>
    <meta name="description" content="Admin dashboard for e-Barangay ni Kap. Manage users, residents, certificates, reports, and more.">
    <meta name="keywords" content="admin dashboard, barangay management, e-barangay">
    <link rel="icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/custom.js"></script>
</head>
<body class="admin-dashboard">
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
    <aside class="sidebar bg-primary-blue" id="adminSidebar">
        <div class="sidebar-header p-3 text-center">
            <img src="../assets/images/sjlg.png" alt="Logo" class="img-fluid mb-2" style="height:48px;">
            <h5 class="text-white mb-0">Admin Panel</h5>
        </div>
        <nav class="sidebar-nav nav flex-column px-2">
            <a class="nav-link text-white" href="#" data-bs-toggle="tooltip" title="Dashboard"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            <a class="nav-link text-white" href="../admin/users.php" data-bs-toggle="tooltip" title="User Management"><i class="fas fa-users-cog me-2"></i> Users</a>
            <a class="nav-link text-white" href="../admin/residents.php" data-bs-toggle="tooltip" title="Resident Management"><i class="fas fa-users me-2"></i> Residents</a>
            <a class="nav-link text-white" href="../admin/certificates.php" data-bs-toggle="tooltip" title="Certificate Requests"><i class="fas fa-file-alt me-2"></i> Certificates</a>
            <a class="nav-link text-white" href="../admin/blotter.php" data-bs-toggle="tooltip" title="Blotter Reports"><i class="fas fa-book me-2"></i> Blotter</a>
            <a class="nav-link text-white" href="../admin/health.php" data-bs-toggle="tooltip" title="Health Records"><i class="fas fa-notes-medical me-2"></i> Health</a>
            <a class="nav-link text-white" href="../admin/announcements.php" data-bs-toggle="tooltip" title="Announcements"><i class="fas fa-bullhorn me-2"></i> Announcements</a>
            <a class="nav-link text-white" href="../admin/events.php" data-bs-toggle="tooltip" title="Events"><i class="fas fa-calendar-alt me-2"></i> Events</a>
            <a class="nav-link text-white" href="../admin/settings.php" data-bs-toggle="tooltip" title="System Settings"><i class="fas fa-cogs me-2"></i> Settings</a>
            <a class="nav-link text-white" href="../admin/activity.php" data-bs-toggle="tooltip" title="Activity Logs"><i class="fas fa-clipboard-list me-2"></i> Activity Logs</a>
            <a class="nav-link text-white" href="../admin/reports.php" data-bs-toggle="tooltip" title="Statistics & Reports"><i class="fas fa-chart-bar me-2"></i> Reports</a>
            <a class="nav-link text-danger mt-2" href="../auth/logout.php" data-bs-toggle="tooltip" title="Logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </aside>
    <!-- Main Content -->
    <main class="main-wrapper">
        <div class="container-fluid py-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card stat-card modern-card shadow-sm" data-bs-toggle="tooltip" title="Total Active Users">
                        <div class="card-body text-center">
                            <div class="stat-icon-wrapper mb-2"><i class="fas fa-users fa-2x text-golden-yellow"></i></div>
                            <div class="stat-number fs-2 fw-bold"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label">Active Users</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card modern-card shadow-sm" data-bs-toggle="tooltip" title="Total Residents">
                        <div class="card-body text-center">
                            <div class="stat-icon-wrapper mb-2"><i class="fas fa-user-friends fa-2x text-info-blue-500"></i></div>
                            <div class="stat-number fs-2 fw-bold"><?php echo $stats['total_residents']; ?></div>
                            <div class="stat-label">Residents</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card modern-card shadow-sm" data-bs-toggle="tooltip" title="Pending Certificate Requests">
                        <div class="card-body text-center">
                            <div class="stat-icon-wrapper mb-2"><i class="fas fa-file-alt fa-2x text-warning"></i></div>
                            <div class="stat-number fs-2 fw-bold"><?php echo $stats['pending_requests']; ?></div>
                            <div class="stat-label">Pending Requests</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card modern-card shadow-sm" data-bs-toggle="tooltip" title="Published Announcements">
                        <div class="card-body text-center">
                            <div class="stat-icon-wrapper mb-2"><i class="fas fa-bullhorn fa-2x text-success"></i></div>
                            <div class="stat-number fs-2 fw-bold"><?php echo $stats['total_announcements']; ?></div>
                            <div class="stat-label">Announcements</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card modern-card shadow-sm">
                        <div class="card-header-modern d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">System Overview</h5>
                        </div>
                        <div class="card-body-modern">
                            <p class="mb-0">Manage users, residents, certificates, reports, and more from your admin dashboard. Use the sidebar to navigate between modules. All data is live and up-to-date.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Footer -->
    <footer class="footer bg-dark-navy text-white py-3 mt-auto">
        <div class="container-fluid text-center">
            <span>&copy; <?php echo date('Y'); ?> e-Barangay ni Kap. All rights reserved. | Admin Dashboard</span>
        </div>
    </footer>
    <script>
    // Sidebar toggle
    $(document).ready(function() {
        $('#sidebarToggle').on('click', function() {
            $('#adminSidebar').toggleClass('show');
        });
    });
    </script>
</body>
</html>