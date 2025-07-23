<?php
/**
 * Email Notifier Class
 * Handles email notifications for certificate requests using PHPMailer
 * e-Barangay ni Kap
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailNotifier {
    private $mailer;
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeMailer();
    }
    
    /**
     * Initialize PHPMailer
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            
            // Default settings
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log('Email initialization error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send certificate request status notification
     */
    public function sendRequestStatusNotification($requestId, $status, $remarks = '') {
        try {
            // Get request details
            $request = $this->getRequestDetails($requestId);
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            // Set recipient
            $this->mailer->addAddress($request['email'], $request['resident_name']);
            
            // Set subject and body
            $subject = $this->getStatusSubject($status, $request['certificate_type']);
            $body = $this->getStatusEmailBody($request, $status, $remarks);
            
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);
            
            // Send email
            $result = $this->mailer->send();
            
            // Log email sent
            $this->logEmailSent($requestId, $request['email'], $subject, $status);
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Email sending error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send certificate completion notification
     */
    public function sendCertificateCompletionNotification($requestId) {
        try {
            // Get request details
            $request = $this->getRequestDetails($requestId);
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            // Set recipient
            $this->mailer->addAddress($request['email'], $request['resident_name']);
            
            // Set subject and body
            $subject = 'Certificate Ready for Download - ' . $request['certificate_type'];
            $body = $this->getCompletionEmailBody($request);
            
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);
            
            // Send email
            $result = $this->mailer->send();
            
            // Log email sent
            $this->logEmailSent($requestId, $request['email'], $subject, 'completed');
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Email sending error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send welcome email to new residents
     */
    public function sendWelcomeEmail($userId) {
        try {
            // Get user details
            $user = $this->getUserDetails($userId);
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Set recipient
            $this->mailer->addAddress($user['email'], $user['full_name']);
            
            // Set subject and body
            $subject = 'Welcome to e-Barangay ni Kap - ' . $user['full_name'];
            $body = $this->getWelcomeEmailBody($user);
            
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);
            
            // Send email
            $result = $this->mailer->send();
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Email sending error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get request details
     */
    private function getRequestDetails($requestId) {
        $sql = "SELECT cr.*, CONCAT(u.first_name, ' ', u.last_name) as resident_name, 
                       u.email, u.phone
                FROM certificate_requests cr
                JOIN users u ON cr.resident_id = u.id
                WHERE cr.id = ?";
        
        return $this->db->fetchOne($sql, [$requestId]);
    }
    
    /**
     * Get user details
     */
    private function getUserDetails($userId) {
        $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name, email, username
                FROM users WHERE id = ?";
        
        return $this->db->fetchOne($sql, [$userId]);
    }
    
    /**
     * Get status email subject
     */
    private function getStatusSubject($status, $certificateType) {
        $statusText = ucfirst($status);
        return "Certificate Request Update - {$certificateType} ({$statusText})";
    }
    
    /**
     * Get status email body
     */
    private function getStatusEmailBody($request, $status, $remarks) {
        $statusColor = $this->getStatusColor($status);
        $statusText = ucfirst($status);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0359b6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .status { background: {$statusColor}; color: white; padding: 10px; border-radius: 5px; text-align: center; }
                .footer { background: #1d3351; color: white; padding: 15px; text-align: center; font-size: 12px; }
                .btn { display: inline-block; padding: 10px 20px; background: #fdd200; color: #000; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>e-Barangay ni Kap</h2>
                    <p>Barangay San Joaquin, Palo, Leyte</p>
                </div>
                
                <div class='content'>
                    <h3>Certificate Request Update</h3>
                    <p>Dear <strong>{$request['resident_name']}</strong>,</p>
                    
                    <p>Your certificate request has been updated:</p>
                    
                    <div class='status'>
                        <strong>Status: {$statusText}</strong>
                    </div>
                    
                    <h4>Request Details:</h4>
                    <ul>
                        <li><strong>Request ID:</strong> #{$request['id']}</li>
                        <li><strong>Certificate Type:</strong> {$request['certificate_type']}</li>
                        <li><strong>Purpose:</strong> {$request['purpose']}</li>
                        <li><strong>Request Date:</strong> " . formatDate($request['request_date']) . "</li>
                    </ul>
                    
                    " . ($remarks ? "<p><strong>Remarks:</strong> {$remarks}</p>" : "") . "
                    
                    <p>You can track your request status by logging into your account.</p>
                    
                    <p style='text-align: center;'>
                        <a href='" . APP_URL . "/auth/login.php' class='btn'>Login to Portal</a>
                    </p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated message from e-Barangay ni Kap System</p>
                    <p>For inquiries, please contact the barangay office</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get completion email body
     */
    private function getCompletionEmailBody($request) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0359b6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .success { background: #28a745; color: white; padding: 10px; border-radius: 5px; text-align: center; }
                .footer { background: #1d3351; color: white; padding: 15px; text-align: center; font-size: 12px; }
                .btn { display: inline-block; padding: 10px 20px; background: #fdd200; color: #000; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>e-Barangay ni Kap</h2>
                    <p>Barangay San Joaquin, Palo, Leyte</p>
                </div>
                
                <div class='content'>
                    <h3>Certificate Ready for Download</h3>
                    <p>Dear <strong>{$request['resident_name']}</strong>,</p>
                    
                    <div class='success'>
                        <strong>Your certificate is ready!</strong>
                    </div>
                    
                    <h4>Certificate Details:</h4>
                    <ul>
                        <li><strong>Request ID:</strong> #{$request['id']}</li>
                        <li><strong>Certificate Type:</strong> {$request['certificate_type']}</li>
                        <li><strong>Purpose:</strong> {$request['purpose']}</li>
                        <li><strong>Completion Date:</strong> " . date('F j, Y') . "</li>
                    </ul>
                    
                    <p>You can now download your certificate from your account dashboard.</p>
                    
                    <p style='text-align: center;'>
                        <a href='" . APP_URL . "/auth/login.php' class='btn'>Download Certificate</a>
                    </p>
                    
                    <p><strong>Note:</strong> Please download your certificate within 30 days. After that, it will be archived.</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated message from e-Barangay ni Kap System</p>
                    <p>For inquiries, please contact the barangay office</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get welcome email body
     */
    private function getWelcomeEmailBody($user) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0359b6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { background: #1d3351; color: white; padding: 15px; text-align: center; font-size: 12px; }
                .btn { display: inline-block; padding: 10px 20px; background: #fdd200; color: #000; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Welcome to e-Barangay ni Kap</h2>
                    <p>Barangay San Joaquin, Palo, Leyte</p>
                </div>
                
                <div class='content'>
                    <h3>Welcome, {$user['full_name']}!</h3>
                    
                    <p>Thank you for registering with e-Barangay ni Kap. Your account has been successfully created.</p>
                    
                    <h4>Your Account Details:</h4>
                    <ul>
                        <li><strong>Username:</strong> {$user['username']}</li>
                        <li><strong>Email:</strong> {$user['email']}</li>
                    </ul>
                    
                    <h4>Available Services:</h4>
                    <ul>
                        <li>Request certificates online</li>
                        <li>Track request status</li>
                        <li>View announcements</li>
                        <li>Access community information</li>
                    </ul>
                    
                    <p style='text-align: center;'>
                        <a href='" . APP_URL . "/auth/login.php' class='btn'>Login to Your Account</a>
                    </p>
                    
                    <p>If you have any questions, please don't hesitate to contact the barangay office.</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated message from e-Barangay ni Kap System</p>
                    <p>For inquiries, please contact the barangay office</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get status color
     */
    private function getStatusColor($status) {
        switch (strtolower($status)) {
            case 'approved':
                return '#28a745';
            case 'rejected':
                return '#dc3545';
            case 'processing':
                return '#ffc107';
            case 'completed':
                return '#17a2b8';
            default:
                return '#6c757d';
        }
    }
    
    /**
     * Log email sent
     */
    private function logEmailSent($requestId, $email, $subject, $status) {
        $data = [
            'request_id' => $requestId,
            'email' => $email,
            'subject' => $subject,
            'status' => $status,
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('email_logs', $data);
    }
    
    /**
     * Clear recipients
     */
    public function clearRecipients() {
        $this->mailer->clearAddresses();
    }
}
?> 