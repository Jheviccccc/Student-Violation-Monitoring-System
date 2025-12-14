<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = $_SESSION['user'];
if ($user['role'] !== 'viewer') {
	if ($user['role'] === 'student') {
		header('Location: student.php');
		exit;
	}
	header('Location: dashboard.php');
	exit;
}
$db = get_db_connection();

$list = $db->query("SELECT vr.id, vr.occurred_at, s.student_no, s.first_name, s.last_name, v.title, v.category, v.severity, vr.disposition
  FROM violation_records vr
  JOIN students s ON s.id = vr.student_id
  JOIN violations v ON v.id = vr.violation_id
  ORDER BY vr.created_at DESC
  LIMIT 100");
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/nav.php'; ?>
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h4 class="mb-0">View-Only Records</h4>
	</div>
	<div class="card">
		<div class="table-responsive">
			<table class="table table-sm mb-0">
				<thead><tr><th>Date</th><th>Student</th><th>Violation</th><th>Category</th><th>Severity</th><th>Disposition</th></tr></thead>
				<tbody>
					<?php while ($r = $list->fetch_assoc()): ?>
						<tr>
							<td><?php echo h($r['occurred_at']); ?></td>
							<td><?php echo h($r['student_no'] . ' - ' . $r['last_name'] . ', ' . $r['first_name']); ?></td>
							<td><?php echo h($r['title']); ?></td>
							<td><?php echo h($r['category']); ?></td>
							<td><span class="badge text-bg-<?php echo $r['severity']==='high'?'danger':($r['severity']==='medium'?'warning':'secondary'); ?>"><?php echo h($r['severity']); ?></span></td>
							<td><?php echo h($r['disposition']); ?></td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
