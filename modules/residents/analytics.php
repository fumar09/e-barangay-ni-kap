<?php
require_once '../../includes/bootstrap.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

if (!hasPermission('resident_management')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access analytics.');
    redirectToDashboard();
}

$pageTitle = 'Resident Analytics';
$currentPage = 'residents';

$db = getDatabaseConnection();

$year = $_GET['year'] ?? date('Y');
$purok_filter = $_GET['purok_id'] ?? '';

$puroks_query = "SELECT id, name FROM puroks WHERE is_active = 1 ORDER BY name";
$puroks_stmt = $db->query($puroks_query);
$puroks = $puroks_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get population growth data
$growth_query = "
    SELECT year, SUM(total_population) as total_population
    FROM census_data
    GROUP BY year
    ORDER BY year DESC
    LIMIT 10
";
$growth_stmt = $db->query($growth_query);
$growth_data = $growth_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get age distribution data
$age_distribution_query = "
    SELECT 
        SUM(age_0_14) as age_0_14,
        SUM(age_15_64) as age_15_64,
        SUM(age_65_above) as age_65_above
    FROM census_data
    WHERE year = ?
";
$age_distribution_stmt = $db->prepare($age_distribution_query);
$age_distribution_stmt->execute([$year]);
$age_distribution = $age_distribution_stmt->fetch(PDO::FETCH_ASSOC);

// Get education distribution
$education_query = "
    SELECT 
        education,
        COUNT(*) as count
    FROM residents
    WHERE is_active = 1
    GROUP BY education
    ORDER BY count DESC
";
$education_stmt = $db->query($education_query);
$education_data = $education_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get gender distribution by purok
$gender_by_purok_query = "
    SELECT 
        p.name as purok_name,
        COUNT(CASE WHEN r.gender = 'Male' THEN 1 END) as male_count,
        COUNT(CASE WHEN r.gender = 'Female' THEN 1 END) as female_count
    FROM residents r
    LEFT JOIN puroks p ON r.purok_id = p.id
    WHERE r.is_active = 1
    GROUP BY r.purok_id, p.name
    ORDER BY p.name
";
$gender_by_purok_stmt = $db->query($gender_by_purok_query);
$gender_by_purok_data = $gender_by_purok_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get employment statistics
$employment_query = "
    SELECT 
        SUM(employed_count) as total_employed,
        SUM(unemployed_count) as total_unemployed,
        SUM(student_count) as total_students
    FROM census_data
    WHERE year = ?
";
$employment_stmt = $db->prepare($employment_query);
$employment_stmt->execute([$year]);
$employment_data = $employment_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent registrations
$recent_registrations_query = "
    SELECT 
        r.first_name, r.last_name, r.registration_date,
        p.name as purok_name
    FROM residents r
    LEFT JOIN puroks p ON r.purok_id = p.id
    WHERE r.is_active = 1
    ORDER BY r.registration_date DESC
    LIMIT 10
";
$recent_registrations_stmt = $db->query($recent_registrations_query);
$recent_registrations = $recent_registrations_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="analytics-container">
    <div class="container-fluid">
        <div class="analytics-header">
            <div class="resident-header-content">
                <h1>Resident Analytics</h1>
                <p>Comprehensive analysis of resident data and demographic trends</p>
            </div>
        </div>

        <div class="resident-filters">
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="year">Census Year</label>
                        <select id="year" name="year" class="form-control">
                            <?php for ($y = date('Y'); $y >= date('Y') - 10; $y--): ?>
                                <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="purok_id">Filter by Purok</label>
                        <select id="purok_id" name="purok_id" class="form-control">
                            <option value="">All Puroks</option>
                            <?php foreach ($puroks as $purok): ?>
                                <option value="<?= $purok['id'] ?>" 
                                        <?= $purok_filter == $purok['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($purok['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="analytics.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="analytics-summary">
            <div class="analytics-summary-card fade-in-up">
                <div class="resident-stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="resident-stat-number">
                    <?= number_format($age_distribution['age_0_14'] + $age_distribution['age_15_64'] + $age_distribution['age_65_above']) ?>
                </div>
                <div class="resident-stat-label">Total Population (<?= $year ?>)</div>
            </div>
            
            <div class="analytics-summary-card fade-in-up">
                <div class="resident-stat-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="resident-stat-number">
                    <?php 
                    $total = $age_distribution['age_0_14'] + $age_distribution['age_15_64'] + $age_distribution['age_65_above'];
                    echo $total > 0 ? round(($age_distribution['age_15_64'] / $total) * 100, 1) : 0;
                    ?>%
                </div>
                <div class="resident-stat-label">Working Age Population</div>
            </div>
            
            <div class="analytics-summary-card fade-in-up">
                <div class="resident-stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="resident-stat-number">
                    <?php 
                    $college_count = 0;
                    foreach ($education_data as $edu) {
                        if (in_array($edu['education'], ['College', 'Post Graduate'])) {
                            $college_count += $edu['count'];
                        }
                    }
                    echo number_format($college_count);
                    ?>
                </div>
                <div class="resident-stat-label">College Educated</div>
            </div>
            
            <div class="analytics-summary-card fade-in-up">
                <div class="resident-stat-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="resident-stat-number">
                    <?= number_format($employment_data['total_employed'] ?? 0) ?>
                </div>
                <div class="resident-stat-label">Employed Residents</div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="analytics-chart-container">
                    <div class="census-chart-header">
                        <h3>Population Growth Trend</h3>
                        <p>Historical population data over the years</p>
                    </div>
                    
                    <div class="analytics-chart" id="populationGrowthChart">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="analytics-chart-container">
                    <div class="census-chart-header">
                        <h3>Age Distribution</h3>
                        <p>Population breakdown by age groups</p>
                    </div>
                    
                    <div class="analytics-chart" id="ageDistributionChart">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="analytics-chart-container">
                    <div class="census-chart-header">
                        <h3>Gender Distribution by Purok</h3>
                        <p>Male and female population by area</p>
                    </div>
                    
                    <div class="analytics-chart" id="genderByPurokChart">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="analytics-chart-container">
                    <div class="census-chart-header">
                        <h3>Education Level Distribution</h3>
                        <p>Resident education attainment levels</p>
                    </div>
                    
                    <div class="analytics-chart" id="educationChart">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="analytics-chart-container">
                    <div class="census-chart-header">
                        <h3>Employment Status</h3>
                        <p>Employment distribution for <?= $year ?></p>
                    </div>
                    
                    <div class="analytics-chart" id="employmentChart">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="analytics-chart-container">
                    <div class="census-chart-header">
                        <h3>Recent Registrations</h3>
                        <p>Latest resident registrations</p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Resident</th>
                                    <th>Purok</th>
                                    <th>Registration Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_registrations as $registration): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($registration['purok_name'] ?: 'Not assigned') ?>
                                        </td>
                                        <td>
                                            <?= date('M d, Y', strtotime($registration['registration_date'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeTooltips();
    
    const yearSelect = document.getElementById('year');
    const purokSelect = document.getElementById('purok_id');
    
    function performFilter() {
        const form = document.querySelector('form');
        form.submit();
    }
    
    yearSelect.addEventListener('change', performFilter);
    purokSelect.addEventListener('change', performFilter);
    
    // Population Growth Chart
    const growthData = <?= json_encode($growth_data) ?>;
    const growthCtx = document.getElementById('populationGrowthChart');
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: growthData.map(item => item.year).reverse(),
            datasets: [{
                label: 'Total Population',
                data: growthData.map(item => item.total_population).reverse(),
                borderColor: 'rgba(3, 89, 182, 1)',
                backgroundColor: 'rgba(3, 89, 182, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Population'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Year'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Age Distribution Chart
    const ageData = <?= json_encode($age_distribution) ?>;
    const ageCtx = document.getElementById('ageDistributionChart');
    new Chart(ageCtx, {
        type: 'doughnut',
        data: {
            labels: ['0-14 years', '15-64 years', '65+ years'],
            datasets: [{
                data: [ageData.age_0_14, ageData.age_15_64, ageData.age_65_above],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Gender by Purok Chart
    const genderData = <?= json_encode($gender_by_purok_data) ?>;
    const genderCtx = document.getElementById('genderByPurokChart');
    new Chart(genderCtx, {
        type: 'bar',
        data: {
            labels: genderData.map(item => item.purok_name),
            datasets: [{
                label: 'Male',
                data: genderData.map(item => item.male_count),
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }, {
                label: 'Female',
                data: genderData.map(item => item.female_count),
                backgroundColor: 'rgba(255, 99, 132, 0.8)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Population'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Purok'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
    
    // Education Chart
    const educationData = <?= json_encode($education_data) ?>;
    const educationCtx = document.getElementById('educationChart');
    new Chart(educationCtx, {
        type: 'pie',
        data: {
            labels: educationData.map(item => item.education),
            datasets: [{
                data: educationData.map(item => item.count),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Employment Chart
    const employmentData = <?= json_encode($employment_data) ?>;
    const employmentCtx = document.getElementById('employmentChart');
    new Chart(employmentCtx, {
        type: 'doughnut',
        data: {
            labels: ['Employed', 'Unemployed', 'Students'],
            datasets: [{
                data: [
                    employmentData.total_employed || 0,
                    employmentData.total_unemployed || 0,
                    employmentData.total_students || 0
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php include '../../templates/footer.php'; ?> 