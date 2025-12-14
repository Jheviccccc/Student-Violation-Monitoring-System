<?php
require_once __DIR__ . '/config.php';

class EmailService {
    private $db;
    private $settings;
    
    public function __construct() {
        $this->db = get_db_connection();
        $this->loadSettings();
    }
    
    private function loadSettings() {
        $result = $this->db->query('SELECT setting_key, setting_value FROM email_settings');
        $this->settings = [];
        while ($row = $result->fetch_assoc()) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    public function isEnabled(): bool {
        return ($this->settings['email_enabled'] ?? '1') === '1';
    }
    
    public function sendEmail($to, $subject, $body, $isHTML = true) {
        if (!$this->isEnabled()) {
            return false;
        }
        
        $fromEmail = $this->settings['from_email'] ?? 'noreply@school.edu';
        $fromName = $this->settings['from_name'] ?? 'SVMS';
        
        // Check if SMTP credentials are available
        $hasSMTPSettings = !empty($this->settings['smtp_username']) && !empty($this->settings['smtp_password']);
        
        // Use SMTP if enabled OR if credentials are available (prefer SMTP over mail())
        if (($this->settings['smtp_enabled'] ?? '0') === '1' || $hasSMTPSettings) {
            return $this->sendViaSMTP($to, $subject, $body, $isHTML, $fromEmail, $fromName);
        }
        
        // Try PHP mail() function as last resort (may not work on XAMPP/localhost)
        return $this->sendViaMail($to, $subject, $body, $isHTML, $fromEmail, $fromName);
    }
    
    private function sendViaMail($to, $subject, $body, $isHTML, $fromEmail, $fromName) {
        // Suppress warnings and handle errors gracefully
        $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);
        
        $headers = [];
        $headers[] = "From: $fromName <$fromEmail>";
        $headers[] = "Reply-To: $fromEmail";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        if ($isHTML) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        }
        
        $result = @mail($to, $subject, $body, implode("\r\n", $headers));
        
        error_reporting($oldErrorReporting);
        
        // If mail() fails, log it and return false
        if (!$result) {
            error_log("Email send failed: PHP mail() function is not properly configured. Please enable SMTP in email settings.");
            return false;
        }
        
        return $result;
    }
    
    private function sendViaSMTP($to, $subject, $body, $isHTML, $fromEmail, $fromName) {
        $host = $this->settings['smtp_host'] ?? 'smtp.gmail.com';
        $port = (int)($this->settings['smtp_port'] ?? 587);
        $username = $this->settings['smtp_username'] ?? '';
        $password = $this->settings['smtp_password'] ?? '';
        $encryption = $this->settings['smtp_encryption'] ?? 'tls';
        
        if (empty($username) || empty($password)) {
            error_log("Email send failed: SMTP credentials not configured. Please set SMTP username and password in email settings.");
            return false;
        }
        
        // Try PHPMailer first if available
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendViaPHPMailer($to, $subject, $body, $isHTML, $fromEmail, $fromName, $host, $port, $username, $password, $encryption);
        }
        
        // Try simple SMTP via sockets if PHPMailer not available
        if (function_exists('fsockopen')) {
            return $this->sendViaSocketSMTP($to, $subject, $body, $isHTML, $fromEmail, $fromName, $host, $port, $username, $password, $encryption);
        }
        
        // Last resort: try mail() but log warning
        error_log("Email send: PHPMailer not available, falling back to mail(). Consider installing PHPMailer or configuring SMTP properly.");
        return $this->sendViaMail($to, $subject, $body, $isHTML, $fromEmail, $fromName);
    }
    
    private function sendViaSocketSMTP($to, $subject, $body, $isHTML, $fromEmail, $fromName, $host, $port, $username, $password, $encryption) {
        // Simple SMTP implementation using sockets
        // Note: This is a basic implementation. For production, use PHPMailer.
        // For SSL (port 465), use ssl:// wrapper. For TLS (port 587), use STARTTLS.
        try {
            $socket = null;
            
            // Handle SSL connection (port 465) - direct SSL connection
            if ($encryption === 'ssl' || $port == 465) {
                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]);
                $socket = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
                if (!$socket) {
                    error_log("SMTP SSL connection failed: $errstr ($errno)");
                    return false;
                }
            } else {
                // Regular connection for TLS (port 587) - will use STARTTLS
                $socket = @fsockopen($host, $port, $errno, $errstr, 30);
                if (!$socket) {
                    error_log("SMTP connection failed: $errstr ($errno)");
                    return false;
                }
            }
            
            // Read initial response
            $response = fgets($socket, 515);
            if (strpos($response, '220') === false) {
                error_log("SMTP server greeting failed: $response");
                fclose($socket);
                return false;
            }
            
            // EHLO
            fputs($socket, "EHLO $host\r\n");
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (preg_match('/^\d{3} /', $line)) {
                    break;
                }
            }
            
            // Start TLS if needed (for port 587)
            if ($encryption === 'tls' && $port != 465) {
                if (strpos($response, 'STARTTLS') !== false || strpos($response, '250') !== false) {
                    fputs($socket, "STARTTLS\r\n");
                    $response = fgets($socket, 515);
                    
                    if (strpos($response, '220') !== false) {
                        // Enable crypto with proper context
                        $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                        }
                        
                        $context = stream_context_create([
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            ]
                        ]);
                        
                        if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                            error_log("SMTP TLS handshake failed");
                            fclose($socket);
                            return false;
                        }
                        
                        // Send EHLO again after TLS
                        fputs($socket, "EHLO $host\r\n");
                        $response = '';
                        while ($line = fgets($socket, 515)) {
                            $response .= $line;
                            if (preg_match('/^\d{3} /', $line)) {
                                break;
                            }
                        }
                    } else {
                        error_log("SMTP STARTTLS not supported: $response");
                        fclose($socket);
                        return false;
                    }
                }
            }
            
            // Auth
            fputs($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 515);
            if (strpos($response, '334') === false) {
                error_log("SMTP AUTH LOGIN not supported: $response");
                fclose($socket);
                return false;
            }
            
            fputs($socket, base64_encode($username) . "\r\n");
            $response = fgets($socket, 515);
            if (strpos($response, '334') === false) {
                error_log("SMTP username rejected: $response");
                fclose($socket);
                return false;
            }
            
            fputs($socket, base64_encode($password) . "\r\n");
            $response = fgets($socket, 515);
            
            if (strpos($response, '235') === false) {
                error_log("SMTP authentication failed: $response");
                fclose($socket);
                return false;
            }
            
            // Mail from
            fputs($socket, "MAIL FROM: <$fromEmail>\r\n");
            $response = fgets($socket, 515);
            if (strpos($response, '250') === false) {
                error_log("SMTP MAIL FROM failed: $response");
                fclose($socket);
                return false;
            }
            
            // RCPT to
            fputs($socket, "RCPT TO: <$to>\r\n");
            $response = fgets($socket, 515);
            if (strpos($response, '250') === false && strpos($response, '251') === false) {
                error_log("SMTP RCPT TO failed: $response");
                fclose($socket);
                return false;
            }
            
            // Data
            fputs($socket, "DATA\r\n");
            $response = fgets($socket, 515);
            if (strpos($response, '354') === false) {
                error_log("SMTP DATA command failed: $response");
                fclose($socket);
                return false;
            }
            
            // Headers and body
            $headers = "From: $fromName <$fromEmail>\r\n";
            $headers .= "To: <$to>\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            if ($isHTML) {
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            }
            
            fputs($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
            $response = fgets($socket, 515);
            
            // Quit
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            return strpos($response, '250') !== false;
        } catch (Exception $e) {
            error_log("SMTP send failed: " . $e->getMessage());
            if (isset($socket) && is_resource($socket)) {
                @fclose($socket);
            }
            return false;
        }
    }
    
    private function sendViaPHPMailer($to, $subject, $body, $isHTML, $fromEmail, $fromName, $host, $port, $username, $password, $encryption) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->SMTPSecure = $encryption;
            $mail->Port = $port;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Email send failed (PHPMailer): " . $e->getMessage());
            return false;
        }
    }
    
    public function sendViolationNotification($student, $violation, $record, $guardianEmail = null) {
        if (!$this->isEnabled()) {
            return false;
        }
        
        $studentName = $student['first_name'] . ' ' . $student['last_name'];
        $violationTitle = $violation['title'];
        $severity = ucfirst($violation['severity']);
        $date = $record['occurred_at'];
        $notes = $record['notes'] ?? 'No additional notes.';
        
        $subject = "Violation Notice: $violationTitle - $studentName";
        
        $body = $this->getViolationEmailTemplate($studentName, $student['student_no'], $violationTitle, $violation['category'], $severity, $date, $notes);
        
        $emails = [];
        
        // Send to guardian if email provided
        if ($guardianEmail && filter_var($guardianEmail, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $guardianEmail;
        }
        
        // Send to student if they have an email account
        $studentUser = $this->db->query("SELECT email FROM users WHERE id = " . (int)($student['user_id'] ?? 0))->fetch_assoc();
        if ($studentUser && !empty($studentUser['email'])) {
            $emails[] = $studentUser['email'];
        }
        
        $sent = false;
        foreach ($emails as $email) {
            if ($this->sendEmail($email, $subject, $body, true)) {
                $sent = true;
            }
        }
        
        return $sent;
    }
    
    private function getViolationEmailTemplate($studentName, $studentNo, $violationTitle, $category, $severity, $date, $notes) {
        $severityColor = [
            'High' => '#dc3545',
            'Medium' => '#ffc107',
            'Low' => '#6c757d'
        ];
        $color = $severityColor[$severity] ?? '#6c757d';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid $color; border-radius: 5px; }
                .severity-badge { display: inline-block; padding: 5px 15px; background: $color; color: white; border-radius: 20px; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⚠️ Violation Notice</h1>
                    <p>Student Violation Monitoring System</p>
                </div>
                <div class='content'>
                    <p>Dear Parent/Guardian,</p>
                    <p>This is to inform you that a violation has been recorded for your child:</p>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0;'>Student Information</h3>
                        <p><strong>Name:</strong> $studentName</p>
                        <p><strong>Student Number:</strong> $studentNo</p>
                    </div>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0;'>Violation Details</h3>
                        <p><strong>Violation:</strong> $violationTitle</p>
                        <p><strong>Category:</strong> $category</p>
                        <p><strong>Severity:</strong> <span class='severity-badge'>$severity</span></p>
                        <p><strong>Date:</strong> $date</p>
                        <p><strong>Notes:</strong> $notes</p>
                    </div>
                    
                    <p>Please contact the school administration if you have any questions or concerns regarding this violation.</p>
                    
                    <div class='footer'>
                        <p>This is an automated notification from the Student Violation Monitoring System.</p>
                        <p>Please do not reply to this email.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

