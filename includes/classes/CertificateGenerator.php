<?php
/**
 * Certificate Generator Class
 * Handles PDF generation for certificates with QR codes and digital signatures
 * e-Barangay ni Kap
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';

use FPDF\FPDF;

class CertificateGenerator extends FPDF {
    private $db;
    private $qrCodePath;
    
    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->qrCodePath = __DIR__ . '/../../assets/uploads/temp/';
        
        // Create temp directory if it doesn't exist
        if (!is_dir($this->qrCodePath)) {
            mkdir($this->qrCodePath, 0755, true);
        }
    }
    
    /**
     * Generate certificate PDF
     */
    public function generateCertificate($requestId) {
        try {
            // Get request details
            $request = $this->getRequestDetails($requestId);
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            // Get certificate template
            $template = $this->getCertificateTemplate($request['certificate_type']);
            if (!$template) {
                throw new Exception('Certificate template not found');
            }
            
            // Generate QR code
            $qrCodeFile = $this->generateQRCode($requestId, $request);
            
            // Create PDF
            $this->AddPage();
            $this->SetFont('Arial', 'B', 16);
            
            // Add header
            $this->addHeader();
            
            // Add certificate content
            $this->addCertificateContent($request, $template);
            
            // Add QR code
            $this->addQRCode($qrCodeFile);
            
            // Add digital signature
            $this->addDigitalSignature();
            
            // Add footer
            $this->addFooter();
            
            // Generate unique filename
            $filename = 'certificate_' . $requestId . '_' . date('YmdHis') . '.pdf';
            $filepath = __DIR__ . '/../../assets/uploads/certificates/' . $filename;
            
            // Create certificates directory if it doesn't exist
            $certDir = dirname($filepath);
            if (!is_dir($certDir)) {
                mkdir($certDir, 0755, true);
            }
            
            // Output PDF
            $this->Output('F', $filepath);
            
            // Save to database
            $this->saveGeneratedCertificate($requestId, $filename, $filepath);
            
            // Clean up QR code file
            if (file_exists($qrCodeFile)) {
                unlink($qrCodeFile);
            }
            
            return $filepath;
            
        } catch (Exception $e) {
            error_log('Certificate generation error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get request details
     */
    private function getRequestDetails($requestId) {
        $sql = "SELECT cr.*, CONCAT(u.first_name, ' ', u.last_name) as resident_name, 
                       u.address, u.birth_date, u.gender, u.civil_status,
                       p.name as purok_name
                FROM certificate_requests cr
                JOIN users u ON cr.resident_id = u.id
                LEFT JOIN puroks p ON u.purok_id = p.id
                WHERE cr.id = ?";
        
        return $this->db->fetchOne($sql, [$requestId]);
    }
    
    /**
     * Get certificate template
     */
    private function getCertificateTemplate($certificateType) {
        $sql = "SELECT * FROM certificate_templates WHERE certificate_type = ? AND is_active = 1";
        return $this->db->fetchOne($sql, [$certificateType]);
    }
    
    /**
     * Generate QR code
     */
    private function generateQRCode($requestId, $request) {
        // Create QR code data
        $qrData = json_encode([
            'request_id' => $requestId,
            'resident_name' => $request['resident_name'],
            'certificate_type' => $request['certificate_type'],
            'issue_date' => date('Y-m-d'),
            'barangay' => 'San Joaquin',
            'municipality' => 'Palo',
            'province' => 'Leyte'
        ]);
        
        // Generate QR code using simple text representation
        // In production, you would use a QR code library like endroid/qr-code
        $qrCodeFile = $this->qrCodePath . 'qr_' . $requestId . '.txt';
        file_put_contents($qrCodeFile, $qrData);
        
        return $qrCodeFile;
    }
    
    /**
     * Add header to PDF
     */
    private function addHeader() {
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0, 10, 'REPUBLIC OF THE PHILIPPINES', 0, 1, 'C');
        $this->Cell(0, 10, 'PROVINCE OF LEYTE', 0, 1, 'C');
        $this->Cell(0, 10, 'MUNICIPALITY OF PALO', 0, 1, 'C');
        $this->Cell(0, 10, 'BARANGAY SAN JOAQUIN', 0, 1, 'C');
        $this->Ln(10);
    }
    
    /**
     * Add certificate content
     */
    private function addCertificateContent($request, $template) {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, strtoupper($request['certificate_type']), 0, 1, 'C');
        $this->Ln(10);
        
        $this->SetFont('Arial', '', 12);
        
        // Replace placeholders in template
        $content = $template['template_content'];
        $content = str_replace('[RESIDENT_NAME]', $request['resident_name'], $content);
        $content = str_replace('[PURPOSE]', $request['purpose'], $content);
        $content = str_replace('[DATE]', date('F j, Y'), $content);
        $content = str_replace('[ADDRESS]', $request['address'], $content);
        $content = str_replace('[PUROK]', $request['purok_name'], $content);
        
        // Add content to PDF
        $this->MultiCell(0, 8, strip_tags($content), 0, 'J');
        $this->Ln(10);
    }
    
    /**
     * Add QR code to PDF
     */
    private function addQRCode($qrCodeFile) {
        $this->SetFont('Arial', '', 8);
        $this->SetXY(10, $this->GetY() + 10);
        $this->Cell(0, 5, 'QR Code Data: ' . file_get_contents($qrCodeFile), 0, 1, 'L');
        $this->Ln(5);
    }
    
    /**
     * Add digital signature
     */
    private function addDigitalSignature() {
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(10, $this->GetY() + 20);
        $this->Cell(0, 10, '_________________________', 0, 1, 'C');
        $this->Cell(0, 5, 'HON. [BARANGAY CAPTAIN NAME]', 0, 1, 'C');
        $this->Cell(0, 5, 'Barangay Captain', 0, 1, 'C');
        $this->Ln(10);
        
        // Add digital signature info
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 5, 'Digitally signed on: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
        $this->Cell(0, 5, 'Certificate ID: ' . uniqid('CERT-'), 0, 1, 'L');
    }
    
    /**
     * Add footer
     */
    private function addFooter() {
        $this->SetFont('Arial', '', 8);
        $this->SetY(-20);
        $this->Cell(0, 5, 'This certificate is computer-generated and is valid without signature.', 0, 1, 'C');
        $this->Cell(0, 5, 'Generated by e-Barangay ni Kap System', 0, 1, 'C');
    }
    
    /**
     * Save generated certificate to database
     */
    private function saveGeneratedCertificate($requestId, $filename, $filepath) {
        $certificateNumber = 'CERT-' . date('Y') . '-' . str_pad($requestId, 6, '0', STR_PAD_LEFT);
        
        $data = [
            'request_id' => $requestId,
            'certificate_number' => $certificateNumber,
            'file_path' => $filepath,
            'file_size' => filesize($filepath),
            'generated_by' => $_SESSION['user_id'] ?? 1
        ];
        
        $this->db->insert('generated_certificates', $data);
        
        // Update request status to completed
        $this->db->update('certificate_requests', 
            ['status' => 'Completed', 'processed_date' => date('Y-m-d H:i:s')], 
            ['id' => $requestId]
        );
    }
    
    /**
     * Download certificate
     */
    public function downloadCertificate($requestId) {
        $sql = "SELECT * FROM generated_certificates WHERE request_id = ? ORDER BY generated_at DESC LIMIT 1";
        $certificate = $this->db->fetchOne($sql, [$requestId]);
        
        if (!$certificate || !file_exists($certificate['file_path'])) {
            throw new Exception('Certificate file not found');
        }
        
        // Mark as downloaded
        $this->db->update('generated_certificates', 
            ['is_downloaded' => 1, 'downloaded_at' => date('Y-m-d H:i:s')], 
            ['id' => $certificate['id']]
        );
        
        return $certificate['file_path'];
    }
}
?> 