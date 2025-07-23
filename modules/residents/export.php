<?php
require_once '../../includes/bootstrap.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

if (!isLoggedIn()) {
    redirectToLogin();
}

if (!hasPermission('resident_management')) {
    setFlashMessage('error', 'Access denied. You do not have permission to export data.');
    redirectToDashboard();
}

$pageTitle = 'Export Data';
$currentPage = 'residents';

$db = getDatabaseConnection();

$export_type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'PDF';
$year = $_GET['year'] ?? date('Y');
$purok_filter = $_GET['purok_id'] ?? '';

$puroks_query = "SELECT id, name FROM puroks WHERE is_active = 1 ORDER BY name";
$puroks_stmt = $db->query($puroks_query);
$puroks = $puroks_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $export_type = $_POST['export_type'] ?? '';
    $format = $_POST['format'] ?? 'PDF';
    $year = $_POST['year'] ?? date('Y');
    $purok_filter = $_POST['purok_id'] ?? '';
    $include_headers = isset($_POST['include_headers']);
    
    if (empty($export_type)) {
        setFlashMessage('error', 'Please select an export type.');
        redirectTo('modules/residents/export.php');
    }
    
    try {
        switch ($export_type) {
            case 'residents':
                exportResidents($db, $format, $purok_filter, $include_headers);
                break;
            case 'census':
                exportCensus($db, $format, $year, $purok_filter, $include_headers);
                break;
            case 'families':
                exportFamilies($db, $format, $purok_filter, $include_headers);
                break;
            case 'analytics':
                exportAnalytics($db, $format, $year, $include_headers);
                break;
            default:
                setFlashMessage('error', 'Invalid export type.');
                redirectTo('modules/residents/export.php');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'An error occurred while generating the export. Please try again.');
        error_log('Export error: ' . $e->getMessage());
        redirectTo('modules/residents/export.php');
    }
}

function exportResidents($db, $format, $purok_filter, $include_headers) {
    $where_conditions = ['r.is_active = 1'];
    $params = [];
    
    if (!empty($purok_filter)) {
        $where_conditions[] = "r.purok_id = ?";
        $params[] = $purok_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $query = "
        SELECT r.*, p.name as purok_name, u.email
        FROM residents r
        LEFT JOIN puroks p ON r.purok_id = p.id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE $where_clause
        ORDER BY r.last_name, r.first_name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $headers = [
        'ID', 'First Name', 'Last Name', 'Middle Name', 'Suffix', 'Birth Date', 'Age',
        'Gender', 'Civil Status', 'Nationality', 'Religion', 'Occupation', 'Education',
        'Contact Number', 'Email', 'Address', 'Emergency Contact', 'Emergency Contact Number',
        'Voter ID', 'Purok', 'Is Head of Family', 'Registration Date'
    ];
    
    $data = [];
    foreach ($residents as $resident) {
        $age = date_diff(date_create($resident['birth_date']), date_create('today'))->y;
        $data[] = [
            $resident['id'],
            $resident['first_name'],
            $resident['last_name'],
            $resident['middle_name'],
            $resident['suffix'],
            $resident['birth_date'],
            $age,
            $resident['gender'],
            $resident['civil_status'],
            $resident['nationality'],
            $resident['religion'],
            $resident['occupation'],
            $resident['education'],
            $resident['contact_number'],
            $resident['email'],
            $resident['address'],
            $resident['emergency_contact'],
            $resident['emergency_contact_number'],
            $resident['voter_id'],
            $resident['purok_name'],
            $resident['is_head_of_family'] ? 'Yes' : 'No',
            $resident['registration_date']
        ];
    }
    
    generateExport($data, $headers, 'Resident_List_' . date('Y-m-d'), $format, $include_headers);
}

function exportCensus($db, $format, $year, $purok_filter, $include_headers) {
    $where_conditions = ['c.year = ?'];
    $params = [$year];
    
    if (!empty($purok_filter)) {
        $where_conditions[] = "c.purok_id = ?";
        $params[] = $purok_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $query = "
        SELECT c.*, p.name as purok_name
        FROM census_data c
        LEFT JOIN puroks p ON c.purok_id = p.id
        WHERE $where_clause
        ORDER BY p.name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $census_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $headers = [
        'Year', 'Purok', 'Total Population', 'Male Count', 'Female Count',
        'Age 0-14', 'Age 15-64', 'Age 65+', 'Employed Count', 'Unemployed Count',
        'Student Count', 'Elementary Education', 'High School Education',
        'College Education', 'Post Graduate Education'
    ];
    
    $data = [];
    foreach ($census_data as $census) {
        $data[] = [
            $census['year'],
            $census['purok_name'],
            $census['total_population'],
            $census['male_count'],
            $census['female_count'],
            $census['age_0_14'],
            $census['age_15_64'],
            $census['age_65_above'],
            $census['employed_count'],
            $census['unemployed_count'],
            $census['student_count'],
            $census['elementary_education'],
            $census['high_school_education'],
            $census['college_education'],
            $census['post_graduate_education']
        ];
    }
    
    generateExport($data, $headers, 'Census_Data_' . $year . '_' . date('Y-m-d'), $format, $include_headers);
}

function exportFamilies($db, $format, $purok_filter, $include_headers) {
    $where_conditions = ['f.is_active = 1'];
    $params = [];
    
    if (!empty($purok_filter)) {
        $where_conditions[] = "f.purok_id = ?";
        $params[] = $purok_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $query = "
        SELECT f.*, p.name as purok_name,
               h.first_name as head_first_name, h.last_name as head_last_name
        FROM family_records f
        LEFT JOIN puroks p ON f.purok_id = p.id
        LEFT JOIN residents h ON f.head_of_family_id = h.id
        WHERE $where_clause
        ORDER BY f.family_name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $families = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $headers = [
        'ID', 'Family Name', 'Head of Family', 'Purok', 'Address', 'Contact Number',
        'Emergency Contact', 'Emergency Contact Number', 'Family Size', 'Monthly Income'
    ];
    
    $data = [];
    foreach ($families as $family) {
        $data[] = [
            $family['id'],
            $family['family_name'],
            $family['head_first_name'] . ' ' . $family['head_last_name'],
            $family['purok_name'],
            $family['address'],
            $family['contact_number'],
            $family['emergency_contact'],
            $family['emergency_contact_number'],
            $family['family_size'],
            number_format($family['monthly_income'], 2)
        ];
    }
    
    generateExport($data, $headers, 'Family_Records_' . date('Y-m-d'), $format, $include_headers);
}

function exportAnalytics($db, $format, $year, $include_headers) {
    // Get summary statistics
    $stats_query = "
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
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([$year]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    $headers = ['Metric', 'Value', 'Percentage'];
    $data = [
        ['Total Population', $stats['total_population'], '100%'],
        ['Male Population', $stats['total_male'], round(($stats['total_male'] / $stats['total_population']) * 100, 1) . '%'],
        ['Female Population', $stats['total_female'], round(($stats['total_female'] / $stats['total_population']) * 100, 1) . '%'],
        ['Age 0-14', $stats['total_age_0_14'], round(($stats['total_age_0_14'] / $stats['total_population']) * 100, 1) . '%'],
        ['Age 15-64', $stats['total_age_15_64'], round(($stats['total_age_15_64'] / $stats['total_population']) * 100, 1) . '%'],
        ['Age 65+', $stats['total_age_65_above'], round(($stats['total_age_65_above'] / $stats['total_population']) * 100, 1) . '%'],
        ['Employed', $stats['total_employed'], round(($stats['total_employed'] / $stats['total_population']) * 100, 1) . '%'],
        ['Unemployed', $stats['total_unemployed'], round(($stats['total_unemployed'] / $stats['total_population']) * 100, 1) . '%'],
        ['Students', $stats['total_students'], round(($stats['total_students'] / $stats['total_population']) * 100, 1) . '%']
    ];
    
    generateExport($data, $headers, 'Analytics_Summary_' . $year . '_' . date('Y-m-d'), $format, $include_headers);
}

function generateExport($data, $headers, $filename, $format, $include_headers) {
    if ($format === 'PDF') {
        generatePDF($data, $headers, $filename, $include_headers);
    } elseif ($format === 'Excel') {
        generateExcel($data, $headers, $filename, $include_headers);
    } elseif ($format === 'CSV') {
        generateCSV($data, $headers, $filename, $include_headers);
    }
}

function generatePDF($data, $headers, $filename, $include_headers) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'e-Barangay ni Kap - ' . $filename, 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('Arial', 'B', 10);
    $col_width = 190 / count($headers);
    
    if ($include_headers) {
        foreach ($headers as $header) {
            $pdf->Cell($col_width, 7, $header, 1);
        }
        $pdf->Ln();
    }
    
    $pdf->SetFont('Arial', '', 8);
    foreach ($data as $row) {
        foreach ($row as $cell) {
            $pdf->Cell($col_width, 6, $cell, 1);
        }
        $pdf->Ln();
    }
    
    $pdf->Output($filename . '.pdf', 'D');
    exit;
}

function generateExcel($data, $headers, $filename, $include_headers) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $col = 'A';
    if ($include_headers) {
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        $row = 2;
    } else {
        $row = 1;
    }
    
    foreach ($data as $dataRow) {
        $col = 'A';
        foreach ($dataRow as $cell) {
            $sheet->setCellValue($col . $row, $cell);
            $col++;
        }
        $row++;
    }
    
    $writer = new Xlsx($spreadsheet);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    exit;
}

function generateCSV($data, $headers, $filename, $include_headers) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($include_headers) {
        fputcsv($output, $headers);
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

include '../../templates/header.php';
?>

<div class="resident-management">
    <div class="container-fluid">
        <div class="resident-header">
            <div class="resident-header-content">
                <h1>Export Data</h1>
                <p>Generate reports in PDF, Excel, or CSV format</p>
            </div>
        </div>

        <div class="export-container">
            <div class="export-header">
                <h3>Export Options</h3>
                <p>Select the data type and format for export</p>
            </div>

            <form method="POST" action="">
                <div class="export-options">
                    <div class="export-option" data-type="residents">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h5>Resident List</h5>
                        <p>Complete resident directory with all details</p>
                    </div>
                    
                    <div class="export-option" data-type="census">
                        <i class="fas fa-chart-bar fa-2x mb-2"></i>
                        <h5>Census Data</h5>
                        <p>Demographic and population statistics</p>
                    </div>
                    
                    <div class="export-option" data-type="families">
                        <i class="fas fa-home fa-2x mb-2"></i>
                        <h5>Family Records</h5>
                        <p>Family information and household data</p>
                    </div>
                    
                    <div class="export-option" data-type="analytics">
                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                        <h5>Analytics Summary</h5>
                        <p>Statistical analysis and trends</p>
                    </div>
                </div>

                <div class="export-filters">
                    <h4>Export Settings</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="export_type">Export Type *</label>
                            <select id="export_type" name="export_type" class="form-control" required>
                                <option value="">Select Export Type</option>
                                <option value="residents" <?= $export_type === 'residents' ? 'selected' : '' ?>>Resident List</option>
                                <option value="census" <?= $export_type === 'census' ? 'selected' : '' ?>>Census Data</option>
                                <option value="families" <?= $export_type === 'families' ? 'selected' : '' ?>>Family Records</option>
                                <option value="analytics" <?= $export_type === 'analytics' ? 'selected' : '' ?>>Analytics Summary</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="format">Export Format *</label>
                            <select id="format" name="format" class="form-control" required>
                                <option value="PDF" <?= $format === 'PDF' ? 'selected' : '' ?>>PDF Document</option>
                                <option value="Excel" <?= $format === 'Excel' ? 'selected' : '' ?>>Excel Spreadsheet</option>
                                <option value="CSV" <?= $format === 'CSV' ? 'selected' : '' ?>>CSV File</option>
                            </select>
                        </div>
                        
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
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="include_headers" name="include_headers" 
                                   class="form-check-input" checked>
                            <label class="form-check-label" for="include_headers">
                                Include column headers in export
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-download"></i> Generate Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeTooltips();
    
    const exportOptions = document.querySelectorAll('.export-option');
    const exportTypeSelect = document.getElementById('export_type');
    const yearSelect = document.getElementById('year');
    const purokSelect = document.getElementById('purok_id');
    
    exportOptions.forEach(option => {
        option.addEventListener('click', function() {
            const type = this.dataset.type;
            exportTypeSelect.value = type;
            
            exportOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    
    exportTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        exportOptions.forEach(opt => {
            if (opt.dataset.type === selectedType) {
                opt.classList.add('selected');
            } else {
                opt.classList.remove('selected');
            }
        });
    });
    
    if (exportTypeSelect.value) {
        const selectedOption = document.querySelector(`[data-type="${exportTypeSelect.value}"]`);
        if (selectedOption) {
            selectedOption.classList.add('selected');
        }
    }
});
</script>

<?php include '../../templates/footer.php'; ?> 