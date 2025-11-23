<?php
require_once 'SessionManager.php';
require_once 'DatabaseConnection.php';
require_once 'SecurityManager.php';

/**
 * User Verification System for BTS eLMS
 * Handles student ID verification, email verification, and account validation
 */

class UserVerification {
    private $db;
    private $securityManager;
    
    public function __construct() {
        $database = new DatabaseConnection();
        $this->db = $database->getConnection();
        $this->securityManager = SecurityManager::getInstance();
    }
    
    /**
     * Send email verification code
     */
    public function sendEmailVerification($userId, $email) {
        try {
            // Generate verification code
            $verificationCode = $this->securityManager->generateSecureToken(6);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store verification code
            $stmt = $this->db->prepare("
                INSERT INTO email_verifications (user_id, email, verification_code, expires_at, created_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                verification_code = VALUES(verification_code),
                expires_at = VALUES(expires_at),
                created_at = VALUES(created_at),
                attempts = 0,
                verified = 0
            ");
            $stmt->execute([$userId, $email, $verificationCode, $expiresAt]);
            
            // Send verification email
            $subject = "Verify Your Email - Benguet Technical School eLMS";
            $message = $this->generateEmailVerificationTemplate($verificationCode, $email);
            
            // In a real implementation, you would use a proper email service
            // For now, we'll simulate sending and store in a notifications table
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, title, message, status, created_at)
                VALUES (?, 'email_verification', ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$userId, $subject, $message]);
            
            return [
                'success' => true,
                'message' => 'Verification code sent to your email address',
                'code' => $verificationCode // For testing purposes only
            ];
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send verification email'
            ];
        }
    }
    
    /**
     * Verify email with code
     */
    public function verifyEmailCode($userId, $code) {
        try {
            // Get latest verification record
            $stmt = $this->db->prepare("
                SELECT * FROM email_verifications 
                WHERE user_id = ? AND verification_code = ? 
                AND expires_at > NOW() AND verified = 0 AND attempts < 3
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$userId, $code]);
            $verification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$verification) {
                // Increment attempts for invalid codes
                $stmt = $this->db->prepare("
                    UPDATE email_verifications 
                    SET attempts = attempts + 1 
                    WHERE user_id = ? AND verification_code = ?
                ");
                $stmt->execute([$userId, $code]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid or expired verification code'
                ];
            }
            
            // Mark as verified
            $stmt = $this->db->prepare("
                UPDATE email_verifications 
                SET verified = 1, verified_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$verification['id']]);
            
            // Update user email verification status
            $stmt = $this->db->prepare("
                UPDATE users 
                SET email_verified = 1, email_verified_at = NOW() 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            
            // Log security event
            $this->securityManager->logSecurityEvent('Email Verified', "User ID: {$userId}");
            
            return [
                'success' => true,
                'message' => 'Email verified successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Verification failed'
            ];
        }
    }
    
    /**
     * Verify student ID
     */
    public function verifyStudentId($userId, $studentId, $additionalInfo = []) {
        try {
            // Validate student ID format (example: BTS-2024-001)
            if (!preg_match('/^BTS-\d{4}-\d{3}$/', $studentId)) {
                return [
                    'success' => false,
                    'message' => 'Invalid student ID format. Format: BTS-YYYY-NNN'
                ];
            }
            
            // Check if student ID already exists
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE student_id = ? AND user_id != ?");
            $stmt->execute([$studentId, $userId]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Student ID already exists'
                ];
            }
            
            // Store verification request
            $stmt = $this->db->prepare("
                INSERT INTO student_id_verifications (user_id, student_id, additional_info, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$userId, $studentId, json_encode($additionalInfo)]);
            
            // In a real implementation, this would be verified against school records
            // For now, we'll auto-approve for demonstration
            $this->approveStudentId($userId, $studentId);
            
            return [
                'success' => true,
                'message' => 'Student ID verification submitted successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Student ID verification error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Student ID verification failed'
            ];
        }
    }
    
    /**
     * Approve student ID verification
     */
    public function approveStudentId($userId, $studentId) {
        try {
            // Update verification status
            $stmt = $this->db->prepare("
                UPDATE student_id_verifications 
                SET status = 'approved', verified_at = NOW() 
                WHERE user_id = ? AND student_id = ?
            ");
            $stmt->execute([$userId, $studentId]);
            
            // Update user record
            $stmt = $this->db->prepare("
                UPDATE users 
                SET student_id = ?, student_id_verified = 1, student_id_verified_at = NOW() 
                WHERE user_id = ?
            ");
            $stmt->execute([$studentId, $userId]);
            
            // Log security event
            $this->securityManager->logSecurityEvent('Student ID Approved', "User ID: {$userId}, Student ID: {$studentId}");
            
            return [
                'success' => true,
                'message' => 'Student ID approved successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Student ID approval error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to approve student ID'
            ];
        }
    }
    
    /**
     * Get user verification status
     */
    public function getVerificationStatus($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.email_verified,
                    u.email_verified_at,
                    u.student_id,
                    u.student_id_verified,
                    u.student_id_verified_at,
                    siv.status as student_verification_status
                FROM users u
                LEFT JOIN student_id_verifications siv ON u.user_id = siv.user_id
                WHERE u.user_id = ?
                ORDER BY siv.created_at DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'email_verified' => (bool)$status['email_verified'],
                'email_verified_at' => $status['email_verified_at'],
                'student_id' => $status['student_id'],
                'student_id_verified' => (bool)$status['student_id_verified'],
                'student_id_verified_at' => $status['student_id_verified_at'],
                'student_verification_status' => $status['student_verification_status'] ?? 'not_submitted'
            ];
            
        } catch (Exception $e) {
            error_log("Get verification status error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get verification status'
            ];
        }
    }
    
    /**
     * Check if user is fully verified
     */
    public function isFullyVerified($userId) {
        $status = $this->getVerificationStatus($userId);
        
        if (!$status['success']) {
            return false;
        }
        
        return $status['email_verified'] && $status['student_id_verified'];
    }
    
    /**
     * Generate email verification template
     */
    private function generateEmailVerificationTemplate($code, $email) {
        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; color: white;'>
                    <h1>Benguet Technical School eLMS</h1>
                    <p>Email Verification</p>
                </div>
                
                <div style='padding: 30px; background: #f8fafc; border: 1px solid #e2e8f0;'>
                    <h2 style='color: #2d3748;'>Verify Your Email Address</h2>
                    
                    <p style='color: #4a5568; line-height: 1.6;'>
                        Thank you for registering with Benguet Technical School eLMS. To complete your registration, 
                        please use the verification code below:
                    </p>
                    
                    <div style='background: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; border: 2px dashed #667eea;'>
                        <h3 style='color: #667eea; font-size: 24px; letter-spacing: 2px; margin: 0;'>{$code}</h3>
                        <p style='color: #718096; font-size: 14px; margin: 10px 0 0 0;'>Enter this code in the verification form</p>
                    </div>
                    
                    <p style='color: #718096; font-size: 14px; margin: 20px 0;'>
                        <strong>Important:</strong> This code will expire in 1 hour for security reasons.
                    </p>
                    
                    <div style='background: #fed7d7; border: 1px solid #feb2b2; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                        <p style='color: #c53030; margin: 0; font-size: 14px;'>
                            <strong>Security Notice:</strong> If you didn't request this verification, 
                            please ignore this email or contact our support team.
                        </p>
                    </div>
                    
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                    
                    <p style='color: #a0aec0; font-size: 12px; text-align: center; margin: 0;'>
                        Benguet Technical School eLMS<br>
                        This is an automated message. Please do not reply to this email.
                    </p>
                </div>
            </div>
        ";
    }
}

// Global verification functions
function send_email_verification($userId, $email) {
    $verification = new UserVerification();
    return $verification->sendEmailVerification($userId, $email);
}

function verify_email_code($userId, $code) {
    $verification = new UserVerification();
    return $verification->verifyEmailCode($userId, $code);
}

function verify_student_id($userId, $studentId, $additionalInfo = []) {
    $verification = new UserVerification();
    return $verification->verifyStudentId($userId, $studentId, $additionalInfo);
}

function get_verification_status($userId) {
    $verification = new UserVerification();
    return $verification->getVerificationStatus($userId);
}

function is_fully_verified($userId) {
    $verification = new UserVerification();
    return $verification->isFullyVerified($userId);
}

?>