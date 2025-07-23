-- =====================================================
-- e-Barangay ni Kap - Complete Database Schema
-- Phase 1 & Phase 2 Implementation
-- Created: July 15, 2025
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS ebarangay_ni_kap
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ebarangay_ni_kap;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Roles table
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    role_id INT NOT NULL,
    profile_photo VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role_id),
    INDEX idx_active (is_active)
);

-- Puroks table
CREATE TABLE puroks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    leader_id INT,
    population INT DEFAULT 0,
    boundaries TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_leader (leader_id),
    INDEX idx_active (is_active)
);

-- =====================================================
-- RESIDENT MANAGEMENT TABLES
-- =====================================================

-- Family records table
CREATE TABLE family_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_name VARCHAR(200) NOT NULL,
    head_of_family_id INT,
    purok_id INT NOT NULL,
    address TEXT NOT NULL,
    contact_number VARCHAR(20),
    emergency_contact VARCHAR(100),
    emergency_contact_number VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (head_of_family_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (purok_id) REFERENCES puroks(id) ON DELETE RESTRICT,
    INDEX idx_family_name (family_name),
    INDEX idx_head (head_of_family_id),
    INDEX idx_purok (purok_id),
    INDEX idx_active (is_active)
);

-- Residents table
CREATE TABLE residents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    purok_id INT NOT NULL,
    family_id INT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    suffix VARCHAR(10),
    birth_date DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    civil_status ENUM('Single', 'Married', 'Widowed', 'Divorced', 'Separated') NOT NULL,
    nationality VARCHAR(50) DEFAULT 'Filipino',
    religion VARCHAR(100),
    occupation VARCHAR(100),
    education ENUM('None', 'Elementary', 'High School', 'Vocational', 'College', 'Post Graduate') NOT NULL,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT NOT NULL,
    emergency_contact VARCHAR(100),
    emergency_contact_number VARCHAR(20),
    voter_id VARCHAR(50),
    is_head_of_family BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    registration_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (purok_id) REFERENCES puroks(id) ON DELETE RESTRICT,
    FOREIGN KEY (family_id) REFERENCES family_records(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_purok (purok_id),
    INDEX idx_family (family_id),
    INDEX idx_name (first_name, last_name),
    INDEX idx_birth_date (birth_date),
    INDEX idx_active (is_active),
    INDEX idx_registration (registration_date)
);

-- Census data table
CREATE TABLE census_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year INT NOT NULL,
    purok_id INT NOT NULL,
    total_population INT NOT NULL,
    male_count INT NOT NULL,
    female_count INT NOT NULL,
    household_count INT NOT NULL,
    age_group_0_14 INT DEFAULT 0,
    age_group_15_64 INT DEFAULT 0,
    age_group_65_above INT DEFAULT 0,
    employed_count INT DEFAULT 0,
    unemployed_count INT DEFAULT 0,
    student_count INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (purok_id) REFERENCES puroks(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_census (year, purok_id),
    INDEX idx_year (year),
    INDEX idx_purok (purok_id)
);

-- =====================================================
-- CERTIFICATE MANAGEMENT TABLES
-- =====================================================

-- Certificate requests table
CREATE TABLE certificate_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT NOT NULL,
    certificate_type ENUM('Barangay Clearance', 'Certificate of Indigency', 'Certificate of Residency', 'Business Permit') NOT NULL,
    purpose TEXT NOT NULL,
    remarks TEXT,
    status ENUM('Pending', 'Under Review', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL,
    completed_date TIMESTAMP NULL,
    processed_by INT,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_resident (resident_id),
    INDEX idx_type (certificate_type),
    INDEX idx_status (status),
    INDEX idx_request_date (request_date),
    INDEX idx_processed_by (processed_by)
);

-- Certificate templates table
CREATE TABLE certificate_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_type VARCHAR(100) NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    template_content TEXT NOT NULL,
    variables JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (certificate_type),
    INDEX idx_active (is_active)
);

-- Generated certificates table
CREATE TABLE generated_certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    certificate_number VARCHAR(50) UNIQUE NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    qr_code_data TEXT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATE,
    is_valid BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (request_id) REFERENCES certificate_requests(id) ON DELETE CASCADE,
    INDEX idx_request (request_id),
    INDEX idx_certificate_number (certificate_number),
    INDEX idx_generated_at (generated_at),
    INDEX idx_valid (is_valid)
);

-- Request documents table
CREATE TABLE request_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES certificate_requests(id) ON DELETE CASCADE,
    INDEX idx_request (request_id),
    INDEX idx_upload_date (upload_date)
);

-- =====================================================
-- BLOTTER MANAGEMENT TABLES
-- =====================================================

-- Blotter reports table
CREATE TABLE blotter_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_number VARCHAR(50) UNIQUE NOT NULL,
    purok_id INT NOT NULL,
    complainant_id INT,
    respondent_id INT,
    incident_type VARCHAR(100) NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_location TEXT NOT NULL,
    incident_description TEXT NOT NULL,
    complainant_statement TEXT,
    respondent_statement TEXT,
    witnesses TEXT,
    evidence_description TEXT,
    status ENUM('Pending', 'Under Investigation', 'Scheduled for Mediation', 'Resolved', 'Dismissed') DEFAULT 'Pending',
    resolution TEXT,
    resolution_date DATE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (purok_id) REFERENCES puroks(id) ON DELETE RESTRICT,
    FOREIGN KEY (complainant_id) REFERENCES residents(id) ON DELETE SET NULL,
    FOREIGN KEY (respondent_id) REFERENCES residents(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_case_number (case_number),
    INDEX idx_purok (purok_id),
    INDEX idx_complainant (complainant_id),
    INDEX idx_respondent (respondent_id),
    INDEX idx_incident_date (incident_date),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
);

-- =====================================================
-- HEALTH RECORDS TABLES
-- =====================================================

-- Health records table
CREATE TABLE health_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT NOT NULL,
    record_type ENUM('Immunization', 'Medical Check-up', 'Dental', 'Prenatal', 'Emergency', 'Other') NOT NULL,
    health_provider VARCHAR(200),
    diagnosis TEXT,
    treatment TEXT,
    medications TEXT,
    record_date DATE NOT NULL,
    next_appointment DATE,
    is_alert BOOLEAN DEFAULT FALSE,
    alert_description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_resident (resident_id),
    INDEX idx_record_type (record_type),
    INDEX idx_record_date (record_date),
    INDEX idx_alert (is_alert),
    INDEX idx_created_by (created_by)
);

-- Immunization records table
CREATE TABLE immunization_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT NOT NULL,
    vaccine_name VARCHAR(100) NOT NULL,
    vaccine_date DATE NOT NULL,
    next_due_date DATE,
    batch_number VARCHAR(50),
    administered_by VARCHAR(100),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_resident (resident_id),
    INDEX idx_vaccine_name (vaccine_name),
    INDEX idx_vaccine_date (vaccine_date),
    INDEX idx_next_due (next_due_date),
    INDEX idx_created_by (created_by)
);

-- =====================================================
-- COMMUNITY ENGAGEMENT TABLES
-- =====================================================

-- Announcements table
CREATE TABLE announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    category ENUM('General', 'Emergency', 'Event', 'Project', 'Health', 'Security') DEFAULT 'General',
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    is_published BOOLEAN DEFAULT FALSE,
    publish_date TIMESTAMP NULL,
    expiry_date TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_title (title),
    INDEX idx_category (category),
    INDEX idx_priority (priority),
    INDEX idx_published (is_published),
    INDEX idx_publish_date (publish_date),
    INDEX idx_created_by (created_by)
);

-- Events table
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    location VARCHAR(200),
    organizer VARCHAR(100),
    contact_person VARCHAR(100),
    contact_number VARCHAR(20),
    is_public BOOLEAN DEFAULT TRUE,
    max_participants INT,
    current_participants INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_title (title),
    INDEX idx_event_date (event_date),
    INDEX idx_public (is_public),
    INDEX idx_active (is_active),
    INDEX idx_created_by (created_by)
);

-- Feedback table
CREATE TABLE feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    category ENUM('Suggestion', 'Complaint', 'Appreciation', 'Question', 'Other') DEFAULT 'Other',
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Pending', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Pending',
    is_anonymous BOOLEAN DEFAULT FALSE,
    response TEXT,
    responded_by INT,
    response_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_anonymous (is_anonymous),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- NOTIFICATION & ACTIVITY TABLES
-- =====================================================

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT,
    related_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger to update family record when resident is set as head of family
DELIMITER //
CREATE TRIGGER update_family_head
AFTER UPDATE ON residents
FOR EACH ROW
BEGIN
    IF NEW.is_head_of_family = 1 AND OLD.is_head_of_family = 0 THEN
        UPDATE family_records 
        SET head_of_family_id = NEW.id 
        WHERE id = NEW.family_id;
    END IF;
END//
DELIMITER ;

-- Trigger to update purok population when resident is added/removed
DELIMITER //
CREATE TRIGGER update_purok_population_insert
AFTER INSERT ON residents
FOR EACH ROW
BEGIN
    IF NEW.is_active = 1 THEN
        UPDATE puroks 
        SET population = population + 1 
        WHERE id = NEW.purok_id;
    END IF;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER update_purok_population_update
AFTER UPDATE ON residents
FOR EACH ROW
BEGIN
    IF NEW.is_active = 1 AND OLD.is_active = 0 THEN
        UPDATE puroks 
        SET population = population + 1 
        WHERE id = NEW.purok_id;
    ELSEIF NEW.is_active = 0 AND OLD.is_active = 1 THEN
        UPDATE puroks 
        SET population = population - 1 
        WHERE id = NEW.purok_id;
    ELSEIF NEW.purok_id != OLD.purok_id THEN
        UPDATE puroks 
        SET population = population - 1 
        WHERE id = OLD.purok_id;
        UPDATE puroks 
        SET population = population + 1 
        WHERE id = NEW.purok_id;
    END IF;
END//
DELIMITER ;

-- Trigger to generate certificate number
DELIMITER //
CREATE TRIGGER generate_certificate_number
BEFORE INSERT ON generated_certificates
FOR EACH ROW
BEGIN
    IF NEW.certificate_number IS NULL OR NEW.certificate_number = '' THEN
        SET NEW.certificate_number = CONCAT('CERT-', YEAR(NOW()), '-', LPAD(NEW.id, 6, '0'));
    END IF;
END//
DELIMITER ;

-- Trigger to generate case number for blotter reports
DELIMITER //
CREATE TRIGGER generate_case_number
BEFORE INSERT ON blotter_reports
FOR EACH ROW
BEGIN
    IF NEW.case_number IS NULL OR NEW.case_number = '' THEN
        SET NEW.case_number = CONCAT('CASE-', YEAR(NOW()), '-', LPAD(NEW.id, 6, '0'));
    END IF;
END//
DELIMITER ;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert roles
INSERT INTO roles (name, description, permissions) VALUES
('Administrator', 'Full system access and control', '{"all": true}'),
('Staff', 'Barangay staff with limited administrative access', '{"resident_management": true, "certificate_management": true, "blotter_management": true, "health_management": true, "announcement_management": true}'),
('Purok Leader', 'Purok leader with access to purok-specific data', '{"purok_management": true, "resident_view": true, "certificate_view": true, "blotter_view": true}'),
('Resident', 'Regular resident with limited access', '{"certificate_request": true, "profile_management": true, "feedback_submit": true}');

-- Insert sample users with CORRECT password hashes
INSERT INTO users (username, email, password, first_name, last_name, role_id, is_active) VALUES
('admin', 'admin@ebarangay.com', '$2y$12$6ufNFwNLsacC/kLdvcNgI.EVFpyr.2tT/8oB2B5xdYsWSMbbkxRte', 'System', 'Administrator', 1, 1),
('staff', 'staff@ebarangay.com', '$2y$12$YgNRdZrDFPMATQ1szEn.7eKUyRxgMksk5fsV.K1O7nbt6VUOzcU5.', 'Barangay', 'Staff', 2, 1),
('purok', 'purok@ebarangay.com', '$2y$12$tI33tvDRLNIbVj6bVhZgvOlVUypy65dzvmp9EjZ2wdlBo9wjc1rYW', 'Purok', 'Leader', 3, 1),
('resident', 'resident@ebarangay.com', '$2y$12$HyRp.S7QeB/hUODyVgv0N.iS1jIhD60FLcwAuAjkb28KXKglNumt2', 'Sample', 'Resident', 4, 1);

-- Insert sample puroks
INSERT INTO puroks (name, description, leader_id, population) VALUES
('Purok 1 - San Joaquin Proper', 'Main barangay center area', 3, 150),
('Purok 2 - Coastal Area', 'Coastal community area', NULL, 120),
('Purok 3 - Upland Area', 'Upland farming community', NULL, 80),
('Purok 4 - Market Area', 'Commercial and market area', NULL, 200);

-- Insert sample family records
INSERT INTO family_records (family_name, head_of_family_id, purok_id, address, contact_number, emergency_contact, emergency_contact_number) VALUES
('Santos Family', 4, 1, '123 San Joaquin Street, Purok 1', '+639123456789', 'Maria Santos', '+639123456790'),
('Garcia Family', NULL, 1, '456 Barangay Road, Purok 1', '+639123456791', 'Juan Garcia', '+639123456792'),
('Reyes Family', NULL, 2, '789 Coastal Drive, Purok 2', '+639123456793', 'Ana Reyes', '+639123456794'),
('Cruz Family', NULL, 3, '321 Upland Street, Purok 3', '+639123456795', 'Pedro Cruz', '+639123456796');

-- Insert sample residents
INSERT INTO residents (user_id, purok_id, family_id, first_name, last_name, middle_name, birth_date, gender, civil_status, education, address, contact_number, email, is_head_of_family, registration_date) VALUES
(4, 1, 1, 'Sample', 'Resident', 'M', '1990-05-15', 'Male', 'Married', 'College', '123 San Joaquin Street, Purok 1', '+639123456789', 'resident@ebarangay.com', 1, '2025-01-15'),
(NULL, 1, 1, 'Maria', 'Santos', 'G', '1992-08-20', 'Female', 'Married', 'High School', '123 San Joaquin Street, Purok 1', '+639123456790', 'maria.santos@email.com', 0, '2025-01-15'),
(NULL, 1, 2, 'Juan', 'Garcia', 'D', '1985-03-10', 'Male', 'Married', 'College', '456 Barangay Road, Purok 1', '+639123456791', 'juan.garcia@email.com', 1, '2025-01-16'),
(NULL, 2, 3, 'Ana', 'Reyes', 'L', '1988-12-05', 'Female', 'Single', 'Vocational', '789 Coastal Drive, Purok 2', '+639123456793', 'ana.reyes@email.com', 1, '2025-01-17'),
(NULL, 3, 4, 'Pedro', 'Cruz', 'M', '1975-07-22', 'Male', 'Married', 'Elementary', '321 Upland Street, Purok 3', '+639123456795', 'pedro.cruz@email.com', 1, '2025-01-18');

-- Insert sample certificate templates
INSERT INTO certificate_templates (certificate_type, template_name, template_content, variables) VALUES
('Barangay Clearance', 'Standard Clearance', 'This is to certify that [RESIDENT_NAME] is a resident of [BARANGAY_NAME] and is of good moral character...', '["RESIDENT_NAME", "BARANGAY_NAME", "ISSUE_DATE", "PURPOSE"]'),
('Certificate of Indigency', 'Standard Indigency', 'This is to certify that [RESIDENT_NAME] is a resident of [BARANGAY_NAME] and belongs to the indigent sector...', '["RESIDENT_NAME", "BARANGAY_NAME", "ISSUE_DATE", "PURPOSE"]'),
('Certificate of Residency', 'Standard Residency', 'This is to certify that [RESIDENT_NAME] has been a resident of [BARANGAY_NAME] since [RESIDENCE_DATE]...', '["RESIDENT_NAME", "BARANGAY_NAME", "RESIDENCE_DATE", "ISSUE_DATE"]'),
('Business Permit', 'Standard Business Permit', 'This is to certify that [RESIDENT_NAME] is authorized to operate [BUSINESS_NAME] in [BARANGAY_NAME]...', '["RESIDENT_NAME", "BUSINESS_NAME", "BARANGAY_NAME", "ISSUE_DATE"]');

-- Insert sample certificate requests
INSERT INTO certificate_requests (resident_id, certificate_type, purpose, status, request_date) VALUES
(1, 'Barangay Clearance', 'Employment requirement', 'Completed', '2025-01-20 09:00:00'),
(2, 'Certificate of Indigency', 'Scholarship application', 'Pending', '2025-01-21 10:30:00'),
(3, 'Certificate of Residency', 'School enrollment', 'Under Review', '2025-01-22 14:15:00'),
(4, 'Business Permit', 'Small business registration', 'Approved', '2025-01-23 11:45:00');

-- Insert sample blotter reports
INSERT INTO blotter_reports (case_number, purok_id, complainant_id, respondent_id, incident_type, incident_date, incident_location, incident_description, status, created_by) VALUES
('CASE-2025-000001', 1, 1, 2, 'Dispute', '2025-01-15', '123 San Joaquin Street', 'Neighbor dispute over property boundary', 'Resolved', 2),
('CASE-2025-000002', 2, 4, NULL, 'Theft', '2025-01-18', '789 Coastal Drive', 'Reported theft of fishing equipment', 'Under Investigation', 2),
('CASE-2025-000003', 1, 3, NULL, 'Noise Complaint', '2025-01-20', '456 Barangay Road', 'Excessive noise from construction work', 'Scheduled for Mediation', 2);

-- Insert sample health records
INSERT INTO health_records (resident_id, record_type, health_provider, diagnosis, treatment, record_date, created_by) VALUES
(1, 'Medical Check-up', 'Dr. Maria Santos', 'Hypertension', 'Prescribed medication and lifestyle changes', '2025-01-15', 2),
(2, 'Immunization', 'Barangay Health Center', 'COVID-19 Booster', 'Vaccine administered successfully', '2025-01-16', 2),
(3, 'Dental', 'Dr. Juan Garcia', 'Dental cleaning and check-up', 'Regular cleaning completed', '2025-01-17', 2),
(4, 'Prenatal', 'Barangay Health Center', 'Prenatal check-up', 'Mother and baby in good health', '2025-01-18', 2);

-- Insert sample announcements
INSERT INTO announcements (title, content, category, priority, is_published, publish_date, created_by) VALUES
('Barangay Assembly', 'Monthly barangay assembly will be held on January 30, 2025 at 6:00 PM in the barangay hall.', 'Event', 'Medium', 1, '2025-01-25 08:00:00', 1),
('Health Alert', 'Dengue prevention campaign starts this week. Please maintain clean surroundings.', 'Health', 'High', 1, '2025-01-26 09:00:00', 1),
('Road Maintenance', 'Road maintenance work will be conducted on San Joaquin Street from January 28-30, 2025.', 'General', 'Medium', 1, '2025-01-27 10:00:00', 2);

-- Insert sample events
INSERT INTO events (title, description, event_date, start_time, end_time, location, organizer, is_public, created_by) VALUES
('Barangay Fiesta', 'Annual barangay fiesta celebration with cultural activities', '2025-02-15', '08:00:00', '22:00:00', 'Barangay Plaza', 'Barangay Council', 1, 1),
('Health Seminar', 'Free health seminar on nutrition and wellness', '2025-02-20', '14:00:00', '16:00:00', 'Barangay Hall', 'Barangay Health Center', 1, 2),
('Youth Sports Tournament', 'Basketball tournament for barangay youth', '2025-02-25', '09:00:00', '17:00:00', 'Barangay Court', 'SK Chairman', 1, 3);

-- Insert sample feedback
INSERT INTO feedback (user_id, subject, message, category, priority, status, is_anonymous) VALUES
(4, 'Street Light Request', 'Request for additional street lights in Purok 1', 'Suggestion', 'Medium', 'Pending', 0),
(NULL, 'Garbage Collection', 'Irregular garbage collection schedule in Purok 2', 'Complaint', 'High', 'In Progress', 1),
(3, 'Appreciation', 'Thank you for the quick response to our concerns', 'Appreciation', 'Low', 'Resolved', 0);

-- Insert sample census data
INSERT INTO census_data (year, purok_id, total_population, male_count, female_count, household_count, age_group_0_14, age_group_15_64, age_group_65_above, employed_count, unemployed_count, student_count, created_by) VALUES
(2024, 1, 150, 75, 75, 45, 30, 100, 20, 80, 20, 40, 1),
(2024, 2, 120, 60, 60, 35, 25, 80, 15, 65, 15, 30, 1),
(2024, 3, 80, 40, 40, 25, 15, 55, 10, 45, 10, 20, 1),
(2024, 4, 200, 100, 100, 60, 40, 130, 30, 110, 30, 50, 1);

-- Insert sample notifications
INSERT INTO notifications (user_id, title, message, type, related_id, related_type) VALUES
(4, 'Certificate Request Approved', 'Your Barangay Clearance request has been approved.', 'success', 1, 'certificate_request'),
(2, 'Certificate Request Status', 'Your Certificate of Indigency request is under review.', 'info', 2, 'certificate_request'),
(1, 'System Maintenance', 'System will be under maintenance on January 30, 2025 from 2:00-4:00 AM.', 'warning', NULL, 'system');

-- Insert sample activity logs
INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES
(1, 'login', 'User logged in successfully', '192.168.1.100'),
(2, 'certificate_approval', 'Approved certificate request #1', '192.168.1.101'),
(3, 'resident_add', 'Added new resident: Juan Garcia', '192.168.1.102'),
(4, 'certificate_request', 'Requested Barangay Clearance', '192.168.1.103');

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional indexes for better performance
CREATE INDEX idx_residents_search ON residents(first_name, last_name, contact_number);
CREATE INDEX idx_certificate_requests_date ON certificate_requests(request_date, status);
CREATE INDEX idx_blotter_reports_date ON blotter_reports(incident_date, status);
CREATE INDEX idx_health_records_date ON health_records(record_date, record_type);
CREATE INDEX idx_announcements_publish ON announcements(publish_date, is_published);
CREATE INDEX idx_events_date ON events(event_date, is_active);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_activity_logs_user_date ON activity_logs(user_id, created_at);

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- View for resident summary
CREATE VIEW resident_summary AS
SELECT 
    r.id,
    r.first_name,
    r.last_name,
    r.middle_name,
    r.birth_date,
    r.gender,
    r.civil_status,
    r.education,
    r.contact_number,
    r.email,
    p.name as purok_name,
    f.family_name,
    r.is_head_of_family,
    r.registration_date
FROM residents r
LEFT JOIN puroks p ON r.purok_id = p.id
LEFT JOIN family_records f ON r.family_id = f.id
WHERE r.is_active = 1;

-- View for certificate request summary
CREATE VIEW certificate_request_summary AS
SELECT 
    cr.id,
    cr.certificate_type,
    cr.purpose,
    cr.status,
    cr.request_date,
    cr.processed_date,
    cr.completed_date,
    CONCAT(r.first_name, ' ', r.last_name) as resident_name,
    p.name as purok_name,
    u.username as processed_by_name
FROM certificate_requests cr
JOIN residents r ON cr.resident_id = r.id
JOIN puroks p ON r.purok_id = p.id
LEFT JOIN users u ON cr.processed_by = u.id;

-- View for blotter case summary
CREATE VIEW blotter_case_summary AS
SELECT 
    br.id,
    br.case_number,
    br.incident_type,
    br.incident_date,
    br.status,
    p.name as purok_name,
    CONCAT(c.first_name, ' ', c.last_name) as complainant_name,
    CONCAT(resp.first_name, ' ', resp.last_name) as respondent_name,
    u.username as created_by_name
FROM blotter_reports br
JOIN puroks p ON br.purok_id = p.id
LEFT JOIN residents c ON br.complainant_id = c.id
LEFT JOIN residents resp ON br.respondent_id = resp.id
LEFT JOIN users u ON br.created_by = u.id;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Procedure to get dashboard statistics
DELIMITER //
CREATE PROCEDURE GetDashboardStats(IN user_role VARCHAR(50), IN user_id INT)
BEGIN
    DECLARE total_residents INT DEFAULT 0;
    DECLARE total_requests INT DEFAULT 0;
    DECLARE pending_requests INT DEFAULT 0;
    DECLARE total_cases INT DEFAULT 0;
    DECLARE active_cases INT DEFAULT 0;
    
    -- Get total residents
    SELECT COUNT(*) INTO total_residents FROM residents WHERE is_active = 1;
    
    -- Get certificate request stats
    SELECT COUNT(*) INTO total_requests FROM certificate_requests;
    SELECT COUNT(*) INTO pending_requests FROM certificate_requests WHERE status = 'Pending';
    
    -- Get blotter case stats
    SELECT COUNT(*) INTO total_cases FROM blotter_reports;
    SELECT COUNT(*) INTO active_cases FROM blotter_reports WHERE status IN ('Pending', 'Under Investigation', 'Scheduled for Mediation');
    
    -- Return results based on role
    IF user_role = 'Administrator' OR user_role = 'Staff' THEN
        SELECT 
            total_residents as total_residents,
            total_requests as total_requests,
            pending_requests as pending_requests,
            total_cases as total_cases,
            active_cases as active_cases;
    ELSEIF user_role = 'Purok Leader' THEN
        SELECT 
            (SELECT COUNT(*) FROM residents r JOIN puroks p ON r.purok_id = p.id WHERE p.leader_id = user_id AND r.is_active = 1) as purok_residents,
            (SELECT COUNT(*) FROM certificate_requests cr JOIN residents r ON cr.resident_id = r.id JOIN puroks p ON r.purok_id = p.id WHERE p.leader_id = user_id) as purok_requests,
            (SELECT COUNT(*) FROM certificate_requests cr JOIN residents r ON cr.resident_id = r.id JOIN puroks p ON r.purok_id = p.id WHERE p.leader_id = user_id AND cr.status = 'Pending') as pending_requests,
            (SELECT COUNT(*) FROM blotter_reports br JOIN puroks p ON br.purok_id = p.id WHERE p.leader_id = user_id) as purok_cases;
    ELSE
        SELECT 
            (SELECT COUNT(*) FROM certificate_requests WHERE resident_id = user_id) as my_requests,
            (SELECT COUNT(*) FROM certificate_requests WHERE resident_id = user_id AND status = 'Pending') as pending_requests,
            (SELECT COUNT(*) FROM certificate_requests WHERE resident_id = user_id AND status = 'Completed') as completed_requests;
    END IF;
END//
DELIMITER ;

-- Procedure to generate certificate
DELIMITER //
CREATE PROCEDURE GenerateCertificate(IN request_id INT, IN generated_by INT)
BEGIN
    DECLARE cert_number VARCHAR(50);
    DECLARE file_path VARCHAR(255);
    DECLARE cert_type VARCHAR(100);
    DECLARE resident_name VARCHAR(200);
    
    -- Get request details
    SELECT certificate_type INTO cert_type FROM certificate_requests WHERE id = request_id;
    SELECT CONCAT(r.first_name, ' ', r.last_name) INTO resident_name 
    FROM certificate_requests cr 
    JOIN residents r ON cr.resident_id = r.id 
    WHERE cr.id = request_id;
    
    -- Generate certificate number
    SET cert_number = CONCAT('CERT-', YEAR(NOW()), '-', LPAD(request_id, 6, '0'));
    
    -- Generate file path
    SET file_path = CONCAT('/assets/uploads/certificates/', cert_number, '.pdf');
    
    -- Insert generated certificate
    INSERT INTO generated_certificates (request_id, certificate_number, file_path, generated_at)
    VALUES (request_id, cert_number, file_path, NOW());
    
    -- Update request status
    UPDATE certificate_requests 
    SET status = 'Completed', completed_date = NOW(), processed_by = generated_by 
    WHERE id = request_id;
    
    -- Log activity
    INSERT INTO activity_logs (user_id, action, description)
    VALUES (generated_by, 'certificate_generated', CONCAT('Generated certificate: ', cert_number));
    
    SELECT cert_number as certificate_number, file_path as file_path;
END//
DELIMITER ;

-- =====================================================
-- FINAL COMMENTS
-- =====================================================

/*
This complete database schema includes:

1. CORE TABLES:
   - roles, users, puroks

2. RESIDENT MANAGEMENT:
   - family_records, residents, census_data

3. CERTIFICATE MANAGEMENT:
   - certificate_requests, certificate_templates, generated_certificates, request_documents

4. BLOTTER MANAGEMENT:
   - blotter_reports

5. HEALTH RECORDS:
   - health_records, immunization_records

6. COMMUNITY ENGAGEMENT:
   - announcements, events, feedback

7. NOTIFICATION & ACTIVITY:
   - notifications, activity_logs

FEATURES INCLUDED:
- Complete foreign key relationships
- Proper indexing for performance
- Triggers for data integrity
- Stored procedures for common operations
- Views for simplified queries
- Sample data for all user types
- Security features (password hashing, activity logging)

USER ACCOUNTS CREATED:
- admin@ebarangay.com / admin123 (Administrator)
- staff@ebarangay.com / staff123 (Staff)
- purok@ebarangay.com / purok123 (Purok Leader)
- resident@ebarangay.com / resident123 (Resident)

All passwords are hashed using bcrypt with cost 12.
*/ 