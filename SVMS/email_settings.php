<?php
require_once __DIR__ . '/auth.php';
require_role('admin');
require_once __DIR__ . '/email_service.php';

$db = get_db_connection();
$emailService = new EmailService();
$message = '';

if (is_post()) {
    $settings = [
        'email_enabled' => isset($_POST['email_enabled']) ? '1' : '0',
        'smtp_enabled' => isset($_POST['smtp_enabled']) ? '1' : '0',
        'smtp_host' => trim($_POST['smtp_host'] ?? ''),
        'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
        'smtp_username' => trim($_POST['smtp_username'] ?? ''),
        'smtp_encryption' => trim($_POST['smtp_encryption'] ?? 'tls'),
        'from_email' => trim($_POST['from_email'] ?? ''),
        'from_name' => trim($_POST['from_name'] ?? '')
    ];
    
    // Update all settings except password
    $stmt = $db->prepare('INSERT INTO email_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($settings as $key => $value) {
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
    }
    
    // If password is provided, update it; otherwise keep existing
    if (!empty($_POST['smtp_password'])) {
        $password = trim($_POST['smtp_password']);
        $key = 'smtp_password';
        $stmt = $db->prepare('INSERT INTO email_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->bind_param('ss', $key, $password);
        $stmt->execute();
    }
    
    $message = 'Email settings saved successfully!';
}

// Load current settings
$currentSettings = [];
$result = $db->query('SELECT setting_key, setting_value FROM email_settings');
while ($row = $result->fetch_assoc()) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/nav.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-envelope-gear"></i> Email Settings</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> <?php echo h($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="email_enabled" id="email_enabled" 
                                    <?php echo ($currentSettings['email_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_enabled">
                                    <strong>Enable Email Notifications</strong>
                                </label>
                            </div>
                            <small class="text-muted">When enabled, violation notifications will be sent via email.</small>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3"><i class="bi bi-gear"></i> SMTP Configuration</h6>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="smtp_enabled" id="smtp_enabled" 
                                    <?php echo ($currentSettings['smtp_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="smtp_enabled">
                                    Use SMTP (Recommended for production)
                                </label>
                            </div>
                            <small class="text-muted">If disabled, PHP mail() function will be used (may not work on all servers).</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" name="smtp_host" 
                                    value="<?php echo h($currentSettings['smtp_host'] ?? 'smtp.gmail.com'); ?>" 
                                    placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" name="smtp_port" 
                                    value="<?php echo h($currentSettings['smtp_port'] ?? '587'); ?>" 
                                    placeholder="587">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" name="smtp_username" 
                                value="<?php echo h($currentSettings['smtp_username'] ?? ''); ?>" 
                                placeholder="your-email@gmail.com">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" name="smtp_password" 
                                placeholder="Leave blank to keep existing password">
                            <small class="text-muted">For Gmail, use an App Password instead of your regular password.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Encryption</label>
                            <select class="form-select" name="smtp_encryption">
                                <option value="tls" <?php echo ($currentSettings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($currentSettings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3"><i class="bi bi-person-badge"></i> Sender Information</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Email</label>
                                <input type="email" class="form-control" name="from_email" 
                                    value="<?php echo h($currentSettings['from_email'] ?? 'noreply@school.edu'); ?>" 
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Name</label>
                                <input type="text" class="form-control" name="from_name" 
                                    value="<?php echo h($currentSettings['from_name'] ?? 'School Violation Monitoring System'); ?>" 
                                    required>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        <h6><i class="bi bi-info-circle"></i> Setup Instructions</h6>
                        <ul class="mb-0">
                            <li><strong>For Local Development (XAMPP):</strong> You must enable SMTP and configure SMTP settings. PHP mail() function will not work on localhost without a mail server.</li>
                            <li><strong>For Gmail:</strong> Use an App Password (not your regular password). Enable 2-factor authentication first, then generate an App Password.</li>
                            <li><strong>For other providers:</strong> Check their SMTP settings documentation</li>
                            <li><strong>Recommended:</strong> Install PHPMailer via Composer for better email reliability: <code>composer require phpmailer/phpmailer</code></li>
                            <li>Test your settings by creating a violation record with email notification enabled</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <h6><i class="bi bi-exclamation-triangle"></i> Important Notes</h6>
                        <ul class="mb-0">
                            <li>If SMTP is disabled and no mail server is configured, email sending will fail silently</li>
                            <li>For production use, always enable SMTP with proper credentials</li>
                            <li>Email notifications require valid recipient email addresses (guardian contact or student email)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>

