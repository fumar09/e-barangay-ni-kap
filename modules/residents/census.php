<?php
require_once '../../includes/bootstrap.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

if (!hasPermission('resident_management')) {
    setFlashMessage('error', 'Access denied. You do not have permission to access census data.');
    redirectToDashboard();
}

$pageTitle = 'Census Management';
$currentPage = 'residents';

$db = getDatabaseConnection();

$year = $_GET['year'] ?? date('Y');
$purok_filter = $_GET['purok_id'] ?? '';

$puroks_query = "SELECT id, name FROM puroks WHERE is_active = 1 ORDER BY name";
$puroks_stmt = $db->query($puroks_query);
$puroks = $puroks_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    $census_year = $_POST['year'] ?? '';
    $purok_id = $_POST['purok_id'] ?? '';
    $total_population = (int)($_POST['total_population'] ?? 0);
    $male_count = (int)($_POST['male_count'] ?? 0);
    $female_count = (int)($_POST['female_count'] ?? 0);
    $age_0_14 = (int)($_POST['age_0_14'] ?? 0);
    $age_15_64 = (int)($_POST['age_15_64'] ?? 0);
    $age_65_above = (int)($_POST['age_65_above'] ?? 0);
    $employed_count = (int)($_POST['employed_count'] ?? 0);
    $unemployed_count = (int)($_POST['unemployed_count'] ?? 0);
    $student_count = (int)($_POST['student_count'] ?? 0);
    $elementary_education = (int)($_POST['elementary_education'] ?? 0);
    $high_school_education = (int)($_POST['high_school_education'] ?? 0);
    $college_education = (int)($_POST['college_education'] ?? 0);
    $post_graduate_education = (int)($_POST['post_graduate_education'] ?? 0);
    
    if (empty($census_year)) {
        $errors[] = 'Census year is required.';
    }
    
    if (empty($purok_id)) {
        $errors[] = 'Purok is required.';
    }
    
    if ($total_population <= 0) {
        $errors[] = 'Total population must be greater than 0.';
    }
    
    if (($male_count + $female_count) !== $total_population) {
        $errors[] = 'Male and female counts must equal total population.';
    }
    
    if (($age_0_14 + $age_15_64 + $age_65_above) !== $total_population) {
        $errors[] = 'Age group counts must equal total population.';
    }
    
    if (empty($errors)) {
        try {
            $check_query = "SELECT id FROM census_data WHERE year = ? AND purok_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$census_year, $purok_id]);
            
            if ($check_stmt->fetch()) {
                $update_query = "
                    UPDATE census_data SET
                        total_population = ?, male_count = ?, female_count = ?,
                        age_0_14 = ?, age_15_64 = ?, age_65_above = ?,
                        employed_count = ?, unemployed_count = ?, student_count = ?,
                        elementary_education = ?, high_school_education = ?, 
                        college_education = ?, post_graduate_education = ?
                    WHERE year = ? AND purok_id = ?
                ";
                
                $stmt = $db->prepare($update_query);
                $stmt->execute([
                    $total_population, $male_count, $female_count,
                    $age_0_14, $age_15_64, $age_65_above,
                    $employed_count, $unemployed_count, $student_count,
                    $elementary_education, $high_school_education,
                    $college_education, $post_graduate_education,
                    $census_year, $purok_id
                ]);
                
                setFlashMessage('success', 'Census data updated successfully.');
            } else {
                $insert_query = "
                    INSERT INTO census_data (
                        year, purok_id, total_population, male_count, female_count,
                        age_0_14, age_15_64, age_65_above, employed_count, unemployed_count,
                        student_count, elementary_education, high_school_education,
                        college_education, post_graduate_education, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                
                $stmt = $db->prepare($insert_query);
                $stmt->execute([
                    $census_year, $purok_id, $total_population, $male_count, $female_count,
                    $age_0_14, $age_15_64, $age_65_above, $employed_count, $unemployed_count,
                    $student_count, $elementary_education, $high_school_education,
                    $college_education, $post_graduate_education, getCurrentUserId()
                ]);
                
                setFlashMessage('success', 'Census data added successfully.');
            }
            
            redirectTo('modules/residents/census.php?year=' . $census_year);
            
        } catch (Exception $e) {
            $errors[] = 'An error occurred while saving census data. Please try again.';
            error_log('Error saving census data: ' . $e->getMessage());
        }
    }
}

$census_query = "
    SELECT c.*, p.name as purok_name
    FROM census_data c
    LEFT JOIN puroks p ON c.purok_id = p.id
    WHERE c.year = ?
    " . ($purok_filter ? "AND c.purok_id = ?" : "") . "
    ORDER BY p.name
";

$census_params = [$year];
if ($purok_filter) {
    $census_params[] = $purok_filter;
}

$census_stmt = $db->prepare($census_query);
$census_stmt->execute($census_params);
$census_data = $census_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_stats_query = "
    SELECT 
        SUM(total_population) as total_population,
        SUM(male_count) as total_male,
        SUM(female_count) as total_female,
        SUM(age_0_14) as total_age_0_14,
        SUM(age_15_64) as total_age_15_64,
        SUM(age_65_above) as total_age_65_above,
        SUM(employed_count) as total_employed,
        SUM(unemployed_count) as total_unemployed,
        SUM(student_count) as total_students
    FROM census_data 
    WHERE year = ?
";

$total_stats_stmt = $db->prepare($total_stats_query);
$total_stats_stmt->execute([$year]);
$total_stats = $total_stats_stmt->fetch(PDO::FETCH_ASSOC);

include '../../templates/header.php';
?>

<div class="census-container">
    <div class="container-fluid">
        <div class="census-header">
            <div class="resident-header-content">
                <h1>Census Management</h1>
                <p>Track and manage demographic data for barangay residents</p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle"></i> Please correct the following errors:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="census-stats">
            <div class="census-stat-card fade-in-up">
                <div class="resident-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="resident-stat-number"><?= number_format($total_stats['total_population'] ?? 0) ?></div>
                <div class="resident-stat-label">Total Population</div>
            </div>
            
            <div class="census-stat-card fade-in-up">
                <div class="resident-stat-icon">
                    <i class="fas fa-male"></i>
                </div>
                <div class="resident-stat-number"><?= number_format($total_stats['total_male'] ?? 0) ?></div>
                <div class="resident-stat-label">Male Population</div>
            </div>
            
            <div class="census-stat-card fade-in-up">
                <div class="resident-stat-icon">
                    <i class="fas fa-female"></i>
                </div>
                <div class="resident-stat-number"><?= number_format($total_stats['total_female'] ?? 0) ?></div>
                <div class="resident-stat-label">Female Population</div>
            </div>
            
            <div class="census-stat-card fade-in-up">
                <div class="resident-stat-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="resident-stat-number"><?= number_format($total_stats['total_employed'] ?? 0) ?></div>
                <div class="resident-stat-label">Employed</div>
            </div>
            
            <div class="census-stat-card fade-in-up">
                <div class="resident-stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="resident-stat-number"><?= number_format($total_stats['total_students'] ?? 0) ?></div>
                <div class="resident-stat-label">Students</div>
            </div>
        </div>

        <div class="resident-actions">
            <h3>Census Actions</h3>
            <div class="resident-action-buttons">
                <a href="#" class="resident-action-btn" data-bs-toggle="modal" data-bs-target="#addCensusModal">
                    <i class="fas fa-plus"></i>
                    Add Census Data
                </a>
                <a href="export.php?type=census&year=<?= $year ?>" class="resident-action-btn">
                    <i class="fas fa-download"></i>
                    Export Census Data
                </a>
                <a href="analytics.php" class="resident-action-btn">
                    <i class="fas fa-chart-line"></i>
                    View Analytics
                </a>
            </div>
        </div>

        <div class="resident-list-container">
            <div class="resident-list-header">
                <h3>Census Data for <?= $year ?></h3>
                <p>Demographic information by purok</p>
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
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="census.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table resident-table">
                    <thead>
                        <tr>
                            <th>Purok</th>
                            <th>Total Population</th>
                            <th>Male</th>
                            <th>Female</th>
                            <th>Age Groups</th>
                            <th>Employment</th>
                            <th>Education</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($census_data)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                        <p>No census data found for <?= $year ?>.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($census_data as $census): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($census['purok_name']) ?></strong>
                                    </td>
                                    <td>
                                        <strong><?= number_format($census['total_population']) ?></strong>
                                    </td>
                                    <td>
                                        <?= number_format($census['male_count']) ?>
                                        <small class="text-muted">
                                            (<?= $census['total_population'] > 0 ? round(($census['male_count'] / $census['total_population']) * 100, 1) : 0 ?>%)
                                        </small>
                                    </td>
                                    <td>
                                        <?= number_format($census['female_count']) ?>
                                        <small class="text-muted">
                                            (<?= $census['total_population'] > 0 ? round(($census['female_count'] / $census['total_population']) * 100, 1) : 0 ?>%)
                                        </small>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div>0-14: <?= number_format($census['age_0_14']) ?></div>
                                            <div>15-64: <?= number_format($census['age_15_64']) ?></div>
                                            <div>65+: <?= number_format($census['age_65_above']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div>Employed: <?= number_format($census['employed_count']) ?></div>
                                            <div>Unemployed: <?= number_format($census['unemployed_count']) ?></div>
                                            <div>Students: <?= number_format($census['student_count']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div>Elementary: <?= number_format($census['elementary_education']) ?></div>
                                            <div>High School: <?= number_format($census['high_school_education']) ?></div>
                                            <div>College+: <?= number_format($census['college_education'] + $census['post_graduate_education']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="resident-actions-cell">
                                            <a href="edit_census.php?id=<?= $census['id'] ?>" 
                                               class="btn-action btn-edit" 
                                               title="Edit Census Data">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view_census.php?id=<?= $census['id'] ?>" 
                                               class="btn-action btn-view" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="census-chart-container">
            <div class="census-chart-header">
                <h3>Population Distribution by Age Group</h3>
                <p>Visual representation of demographic data</p>
            </div>
            
            <div class="census-chart" id="ageGroupChart">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Census Modal -->
<div class="modal fade" id="addCensusModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Census Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal_year">Census Year *</label>
                                <select id="modal_year" name="year" class="form-control" required>
                                    <?php for ($y = date('Y'); $y >= date('Y') - 10; $y--): ?>
                                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>>
                                            <?= $y ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal_purok_id">Purok *</label>
                                <select id="modal_purok_id" name="purok_id" class="form-control" required>
                                    <option value="">Select Purok</option>
                                    <?php foreach ($puroks as $purok): ?>
                                        <option value="<?= $purok['id'] ?>">
                                            <?= htmlspecialchars($purok['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="modal_total_population">Total Population *</label>
                                <input type="number" id="modal_total_population" name="total_population" 
                                       class="form-control" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="modal_male_count">Male Count *</label>
                                <input type="number" id="modal_male_count" name="male_count" 
                                       class="form-control" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="modal_female_count">Female Count *</label>
                                <input type="number" id="modal_female_count" name="female_count" 
                                       class="form-control" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="modal_age_0_14">Age 0-14 *</label>
                                <input type="number" id="modal_age_0_14" name="age_0_14" 
                                       class="form-control" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="modal_age_15_64">Age 15-64 *</label>
                                <input type="number" id="modal_age_15_64" name="age_15_64" 
                                       class="form-control" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="modal_age_65_above">Age 65+ *</label>
                                <input type="number" id="modal_age_65_above" name="age_65_above" 
                                       class="form-control" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="modal_employed_count">Employed</label>
                                <input type="number" id="modal_employed_count" name="employed_count" 
                                       class="form-control" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="modal_unemployed_count">Unemployed</label>
                                <input type="number" id="modal_unemployed_count" name="unemployed_count" 
                                       class="form-control" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="modal_student_count">Students</label>
                                <input type="number" id="modal_student_count" name="student_count" 
                                       class="form-control" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Census Data</button>
                </div>
            </form>
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
    
    // Chart initialization
    const chartData = <?= json_encode($census_data) ?>;
    if (chartData.length > 0) {
        const ctx = document.getElementById('ageGroupChart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.map(item => item.purok_name),
                datasets: [{
                    label: 'Age 0-14',
                    data: chartData.map(item => item.age_0_14),
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Age 15-64',
                    data: chartData.map(item => item.age_15_64),
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }, {
                    label: 'Age 65+',
                    data: chartData.map(item => item.age_65_above),
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
    }
});
</script>

<?php include '../../templates/footer.php'; ?> 