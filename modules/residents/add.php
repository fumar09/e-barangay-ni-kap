<?php
require_once '../../includes/bootstrap.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

if (!hasPermission('resident_management')) {
    setFlashMessage('error', 'Access denied. You do not have permission to add residents.');
    redirectToDashboard();
}

$pageTitle = 'Add New Resident';
$currentPage = 'residents';

$db = getDatabaseConnection();

$puroks_query = "SELECT id, name FROM puroks WHERE is_active = 1 ORDER BY name";
$puroks_stmt = $db->query($puroks_query);
$puroks = $puroks_stmt->fetchAll(PDO::FETCH_ASSOC);

$families_query = "SELECT id, family_name FROM family_records WHERE is_active = 1 ORDER BY family_name";
$families_stmt = $db->query($families_query);
$families = $families_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $civil_status = $_POST['civil_status'] ?? '';
    $nationality = trim($_POST['nationality'] ?? 'Filipino');
    $religion = trim($_POST['religion'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $education = $_POST['education'] ?? '';
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $emergency_contact_number = trim($_POST['emergency_contact_number'] ?? '');
    $voter_id = trim($_POST['voter_id'] ?? '');
    $purok_id = $_POST['purok_id'] ?? '';
    $family_id = $_POST['family_id'] ?? '';
    $is_head_of_family = isset($_POST['is_head_of_family']) ? 1 : 0;
    
    if (empty($first_name)) {
        $errors[] = 'First name is required.';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required.';
    }
    
    if (empty($birth_date)) {
        $errors[] = 'Birth date is required.';
    } else {
        $birth_date_obj = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$birth_date_obj || $birth_date_obj->format('Y-m-d') !== $birth_date) {
            $errors[] = 'Invalid birth date format.';
        }
    }
    
    if (empty($gender)) {
        $errors[] = 'Gender is required.';
    }
    
    if (empty($civil_status)) {
        $errors[] = 'Civil status is required.';
    }
    
    if (empty($education)) {
        $errors[] = 'Education level is required.';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required.';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    if (!empty($contact_number) && !preg_match('/^[0-9+\-\s()]+$/', $contact_number)) {
        $errors[] = 'Invalid contact number format.';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $registration_date = date('Y-m-d');
            
            $insert_query = "
                INSERT INTO residents (
                    user_id, purok_id, family_id, first_name, last_name, middle_name, suffix,
                    birth_date, gender, civil_status, nationality, religion, occupation, education,
                    contact_number, email, address, emergency_contact, emergency_contact_number,
                    voter_id, is_head_of_family, is_active, registration_date
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?
                )
            ";
            
            $stmt = $db->prepare($insert_query);
            $stmt->execute([
                null, $purok_id, $family_id, $first_name, $last_name, $middle_name, $suffix,
                $birth_date, $gender, $civil_status, $nationality, $religion, $occupation, $education,
                $contact_number, $email, $address, $emergency_contact, $emergency_contact_number,
                $voter_id, $is_head_of_family, $registration_date
            ]);
            
            $resident_id = $db->lastInsertId();
            
            if ($is_head_of_family && empty($family_id)) {
                $family_name = trim($first_name . ' ' . $last_name);
                $family_insert_query = "
                    INSERT INTO family_records (
                        family_name, head_of_family_id, purok_id, address, contact_number,
                        emergency_contact, emergency_contact_number, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ";
                
                $family_stmt = $db->prepare($family_insert_query);
                $family_stmt->execute([
                    $family_name, $resident_id, $purok_id, $address, $contact_number,
                    $emergency_contact, $emergency_contact_number
                ]);
                
                $family_id = $db->lastInsertId();
                
                $update_family_query = "UPDATE residents SET family_id = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_family_query);
                $update_stmt->execute([$family_id, $resident_id]);
            }
            
            $db->commit();
            
            setFlashMessage('success', 'Resident added successfully.');
            redirectTo('modules/residents/view.php?id=' . $resident_id);
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'An error occurred while adding the resident. Please try again.';
            error_log('Error adding resident: ' . $e->getMessage());
        }
    }
}

include '../../templates/header.php';
?>

<div class="resident-management">
    <div class="container-fluid">
        <div class="resident-header">
            <div class="resident-header-content">
                <h1>Add New Resident</h1>
                <p>Enter resident information to create a new profile</p>
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

        <div class="resident-form-container">
            <div class="resident-form-header">
                <h3>Resident Information</h3>
                <p>Fill in the resident's personal and contact information</p>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="resident-form">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?= htmlspecialchars($first_name ?? '') ?>" 
                               class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?= htmlspecialchars($last_name ?? '') ?>" 
                               class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name" 
                               value="<?= htmlspecialchars($middle_name ?? '') ?>" 
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="suffix">Suffix</label>
                        <select id="suffix" name="suffix" class="form-control">
                            <option value="">None</option>
                            <option value="Jr." <?= ($suffix ?? '') === 'Jr.' ? 'selected' : '' ?>>Jr.</option>
                            <option value="Sr." <?= ($suffix ?? '') === 'Sr.' ? 'selected' : '' ?>>Sr.</option>
                            <option value="II" <?= ($suffix ?? '') === 'II' ? 'selected' : '' ?>>II</option>
                            <option value="III" <?= ($suffix ?? '') === 'III' ? 'selected' : '' ?>>III</option>
                            <option value="IV" <?= ($suffix ?? '') === 'IV' ? 'selected' : '' ?>>IV</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="birth_date">Birth Date *</label>
                        <input type="date" id="birth_date" name="birth_date" 
                               value="<?= htmlspecialchars($birth_date ?? '') ?>" 
                               class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?= ($gender ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($gender ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($gender ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="civil_status">Civil Status *</label>
                        <select id="civil_status" name="civil_status" class="form-control" required>
                            <option value="">Select Civil Status</option>
                            <option value="Single" <?= ($civil_status ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                            <option value="Married" <?= ($civil_status ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                            <option value="Widowed" <?= ($civil_status ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                            <option value="Divorced" <?= ($civil_status ?? '') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                            <option value="Separated" <?= ($civil_status ?? '') === 'Separated' ? 'selected' : '' ?>>Separated</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nationality">Nationality</label>
                        <input type="text" id="nationality" name="nationality" 
                               value="<?= htmlspecialchars($nationality ?? 'Filipino') ?>" 
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="religion">Religion</label>
                        <input type="text" id="religion" name="religion" 
                               value="<?= htmlspecialchars($religion ?? '') ?>" 
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="occupation">Occupation</label>
                        <input type="text" id="occupation" name="occupation" 
                               value="<?= htmlspecialchars($occupation ?? '') ?>" 
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="education">Education Level *</label>
                        <select id="education" name="education" class="form-control" required>
                            <option value="">Select Education Level</option>
                            <option value="None" <?= ($education ?? '') === 'None' ? 'selected' : '' ?>>None</option>
                            <option value="Elementary" <?= ($education ?? '') === 'Elementary' ? 'selected' : '' ?>>Elementary</option>
                            <option value="High School" <?= ($education ?? '') === 'High School' ? 'selected' : '' ?>>High School</option>
                            <option value="Vocational" <?= ($education ?? '') === 'Vocational' ? 'selected' : '' ?>>Vocational</option>
                            <option value="College" <?= ($education ?? '') === 'College' ? 'selected' : '' ?>>College</option>
                            <option value="Post Graduate" <?= ($education ?? '') === 'Post Graduate' ? 'selected' : '' ?>>Post Graduate</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" 
                               value="<?= htmlspecialchars($contact_number ?? '') ?>" 
                               class="form-control" placeholder="e.g., 09123456789">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($email ?? '') ?>" 
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="address">Complete Address *</label>
                        <textarea id="address" name="address" class="form-control" 
                                  rows="3" required><?= htmlspecialchars($address ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="emergency_contact">Emergency Contact Person</label>
                        <input type="text" id="emergency_contact" name="emergency_contact" 
                               value="<?= htmlspecialchars($emergency_contact ?? '') ?>" 
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="emergency_contact_number">Emergency Contact Number</label>
                        <input type="text" id="emergency_contact_number" name="emergency_contact_number" 
                               value="<?= htmlspecialchars($emergency_contact_number ?? '') ?>" 
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="voter_id">Voter ID</label>
                        <input type="text" id="voter_id" name="voter_id" 
                               value="<?= htmlspecialchars($voter_id ?? '') ?>" 
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="purok_id">Purok</label>
                        <select id="purok_id" name="purok_id" class="form-control">
                            <option value="">Select Purok</option>
                            <?php foreach ($puroks as $purok): ?>
                                <option value="<?= $purok['id'] ?>" 
                                        <?= ($purok_id ?? '') == $purok['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($purok['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="family_id">Family</label>
                        <select id="family_id" name="family_id" class="form-control">
                            <option value="">Select Family</option>
                            <?php foreach ($families as $family): ?>
                                <option value="<?= $family['id'] ?>" 
                                        <?= ($family_id ?? '') == $family['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($family['family_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="is_head_of_family" name="is_head_of_family" 
                                   class="form-check-input" 
                                   <?= ($is_head_of_family ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_head_of_family">
                                Head of Family
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Resident
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeTooltips();
    
    const birthDateInput = document.getElementById('birth_date');
    const today = new Date();
    const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
    birthDateInput.max = maxDate.toISOString().split('T')[0];
    
    const isHeadOfFamilyCheckbox = document.getElementById('is_head_of_family');
    const familySelect = document.getElementById('family_id');
    
    isHeadOfFamilyCheckbox.addEventListener('change', function() {
        if (this.checked) {
            familySelect.value = '';
            familySelect.disabled = true;
        } else {
            familySelect.disabled = false;
        }
    });
    
    if (isHeadOfFamilyCheckbox.checked) {
        familySelect.disabled = true;
    }
});
</script>

<?php include '../../templates/footer.php'; ?> 