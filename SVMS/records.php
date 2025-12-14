<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/email_service.php';
require_once __DIR__ . '/notification_service.php';
require_role('admin');
$db = get_db_connection();
$user = $_SESSION['user'];

$message = '';
if (is_post()) {
	$student_id = (int)($_POST['student_id'] ?? 0);
	$violation_id = (int)($_POST['violation_id'] ?? 0);
	$occurred_at = $_POST['occurred_at'] ?? date('Y-m-d');
	$notes = trim($_POST['notes'] ?? '');
	$disposition = trim($_POST['disposition'] ?? '');
	$send_email = isset($_POST['send_email']) && $_POST['send_email'] === '1';
	
	$stmt = $db->prepare('INSERT INTO violation_records (student_id, violation_id, recorded_by, occurred_at, notes, disposition) VALUES (?,?,?,?,?,?)');
	$stmt->bind_param('iiisss', $student_id, $violation_id, $user['id'], $occurred_at, $notes, $disposition);
	if ($stmt->execute()) {
		$recordId = $stmt->insert_id;
		
		// Get student and violation details
		$student = $db->query("SELECT * FROM students WHERE id = $student_id")->fetch_assoc();
		$violation = $db->query("SELECT * FROM violations WHERE id = $violation_id")->fetch_assoc();
		$record = ['occurred_at' => $occurred_at, 'notes' => $notes, 'disposition' => $disposition];
		
		// Send email notification if requested
		$emailSent = false;
		if ($send_email && $student && $violation) {
			$emailService = new EmailService();
			$guardianEmail = !empty($student['guardian_contact']) && filter_var($student['guardian_contact'], FILTER_VALIDATE_EMAIL) 
				? $student['guardian_contact'] 
				: null;
			$emailSent = $emailService->sendViolationNotification($student, $violation, $record, $guardianEmail);
		}
		
		// Create in-app notification for admins
		$notificationService = new NotificationService();
		$studentName = $student['first_name'] . ' ' . $student['last_name'];
		$severity = $violation['severity'];
		$notifType = $severity === 'high' ? 'danger' : ($severity === 'medium' ? 'warning' : 'info');
		$notificationService->createForAllAdmins(
			"New Violation Recorded",
			"$studentName - {$violation['title']} ({$violation['category']})",
			$notifType,
			"records.php"
		);
		
		if ($send_email) {
			if ($emailSent) {
				$message = 'Record saved and email notification sent successfully';
			} else {
				$message = 'Record saved, but email notification failed. Please check your email settings in Email Settings page.';
			}
		} else {
			$message = 'Record saved';
		}
	}
}

$students = $db->query('SELECT id, student_no, first_name, last_name FROM students ORDER BY last_name, first_name');
$violations = $db->query('SELECT id, title, severity FROM violations ORDER BY title');

// List last 50 records
$list = $db->query('SELECT vr.id, vr.occurred_at, vr.notes, vr.disposition, s.student_no, s.first_name, s.last_name, v.title, v.severity
  FROM violation_records vr
  JOIN students s ON s.id = vr.student_id
  JOIN violations v ON v.id = vr.violation_id
  ORDER BY vr.created_at DESC
  LIMIT 50');
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/nav.php'; ?>
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h4 class="mb-0">Violation Records</h4>
	</div>
	<?php if ($message): ?>
		<div class="alert alert-<?php echo strpos($message, 'failed') !== false ? 'warning' : 'success'; ?> alert-dismissible fade show">
			<i class="bi bi-<?php echo strpos($message, 'failed') !== false ? 'exclamation-triangle' : 'check-circle'; ?>"></i> 
			<?php echo h($message); ?>
			<?php if (strpos($message, 'failed') !== false): ?>
				<a href="email_settings.php" class="alert-link">Configure Email Settings</a>
			<?php endif; ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	<?php endif; ?>
	<div class="row g-3">
		<div class="col-md-5">
			<div class="card">
				<div class="card-header">Create Record</div>
				<div class="card-body">
					<form method="post">
						<div class="mb-2">
							<label class="form-label">Student</label>
							<select name="student_id" class="form-select form-select-sm" required>
								<option value="">Select student</option>
								<?php while ($s = $students->fetch_assoc()): ?>
									<option value="<?php echo $s['id']; ?>"><?php echo h($s['student_no'] . ' - ' . $s['last_name'] . ', ' . $s['first_name']); ?></option>
								<?php endwhile; ?>
							</select>
						</div>
						<div class="mb-2">
							<label class="form-label">Violation</label>
							<select name="violation_id" class="form-select form-select-sm" required>
								<option value="">Select violation</option>
								<?php while ($v = $violations->fetch_assoc()): 
									$severity_color = $v['severity'] === 'high' ? 'danger' : ($v['severity'] === 'medium' ? 'warning' : 'secondary');
								?>
									<option value="<?php echo $v['id']; ?>" data-severity="<?php echo $v['severity']; ?>">
										<?php echo h($v['title']); ?> - <strong class="severity-<?php echo $v['severity']; ?>"><?php echo ucfirst($v['severity']); ?></strong>
									</option>
								<?php endwhile; ?>
							</select>
						</div>
						<div class="mb-2">
							<label class="form-label">Date</label>
							<input type="date" name="occurred_at" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required>
						</div>
						<div class="mb-2">
							<label class="form-label">Notes</label>
							<textarea name="notes" class="form-control form-control-sm" rows="3"></textarea>
						</div>
						<div class="mb-2">
							<label class="form-label">Disposition</label>
							<input name="disposition" class="form-control form-control-sm">
						</div>
						<div class="mb-3">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="send_email" value="1" id="sendEmail" checked>
								<label class="form-check-label" for="sendEmail">
									<i class="bi bi-envelope"></i> Send email notification to guardian/student
								</label>
							</div>
						</div>
						<div class="d-grid">
							<button class="btn btn-primary btn-sm" type="submit">
								<i class="bi bi-save"></i> Save Record
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="col-md-7">
			<div class="card">
				<div class="card-header">Recent Records</div>
				<div class="table-responsive">
					<table class="table table-sm mb-0">
						<thead><tr><th>Date</th><th>Student</th><th>Violation</th><th>Severity</th><th>Disposition</th></tr></thead>
						<tbody>
							<?php while ($r = $list->fetch_assoc()): ?>
								<tr>
									<td><?php echo h($r['occurred_at']); ?></td>
									<td><?php echo h($r['student_no'] . ' - ' . $r['last_name'] . ', ' . $r['first_name']); ?></td>
									<td><?php echo h($r['title']); ?></td>
									<td>
									<span class="badge text-bg-<?php echo $r['severity']==='high'?'danger':($r['severity']==='medium'?'warning':'secondary'); ?> severity-<?php echo $r['severity']; ?>">
										<?php echo ucfirst(h($r['severity'])); ?>
									</span>
								</td>
									<td><?php echo h($r['disposition']); ?></td>
								</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/footer.php'; ?>

