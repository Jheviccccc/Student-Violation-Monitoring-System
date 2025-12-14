<?php
require_once __DIR__ . '/auth.php';
require_role('admin');
$db = get_db_connection();

$message = '';
if (is_post()) {
	$action = $_POST['action'] ?? '';
	$title = trim($_POST['title'] ?? '');
	$category = trim($_POST['category'] ?? '');
	$severity = $_POST['severity'] ?? 'low';
	$description = trim($_POST['description'] ?? '');
	if ($action === 'create') {
		$stmt = $db->prepare('INSERT INTO violations (title, category, severity, description) VALUES (?,?,?,?)');
		$stmt->bind_param('ssss', $title, $category, $severity, $description);
		if ($stmt->execute()) { $message = 'Violation added'; }
	}
	if ($action === 'update') {
		$id = (int)($_POST['id'] ?? 0);
		$stmt = $db->prepare('UPDATE violations SET title=?, category=?, severity=?, description=? WHERE id=?');
		$stmt->bind_param('ssssi', $title, $category, $severity, $description, $id);
		if ($stmt->execute()) { $message = 'Violation updated'; }
	}
}

if (($_GET['delete'] ?? '') !== '') {
	$id = (int)$_GET['delete'];
	$db->query('DELETE FROM violations WHERE id=' . $id);
	header('Location: violations.php');
	exit;
}

$violations = $db->query('SELECT * FROM violations ORDER BY category, title');

$edit = null;
if (($_GET['edit'] ?? '') !== '') {
	$id = (int)$_GET['edit'];
	$res = $db->query('SELECT * FROM violations WHERE id=' . $id);
	$edit = $res->fetch_assoc();
}
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/nav.php'; ?>
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h4 class="mb-0">Violations</h4>
	</div>
	<?php if ($message): ?><div class="alert alert-success py-2"><?php echo h($message); ?></div><?php endif; ?>
	<div class="row g-3">
		<div class="col-md-7">
			<div class="card">
				<div class="table-responsive">
					<table class="table table-sm mb-0">
						<thead><tr><th>Title</th><th>Category</th><th>Severity</th><th></th></tr></thead>
						<tbody>
							<?php while ($row = $violations->fetch_assoc()): ?>
								<tr>
									<td><?php echo h($row['title']); ?></td>
									<td><?php echo h($row['category']); ?></td>
									<td>
										<span class="badge text-bg-<?php echo $row['severity']==='high'?'danger':($row['severity']==='medium'?'warning':'secondary'); ?> severity-<?php echo $row['severity']; ?>">
											<?php echo ucfirst(h($row['severity'])); ?>
										</span>
									</td>
									<td class="text-end">
										<a class="btn btn-sm btn-outline-primary" href="?edit=<?php echo $row['id']; ?>">Edit</a>
										<a class="btn btn-sm btn-outline-danger" href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this violation?')">Delete</a>
									</td>
								</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-md-5">
			<div class="card">
				<div class="card-header"><?php echo $edit ? 'Edit Violation' : 'Add Violation'; ?></div>
				<div class="card-body">
					<form method="post">
						<input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'create'; ?>">
						<?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
						<div class="mb-2">
							<label class="form-label">Title</label>
							<input class="form-control form-control-sm" name="title" required value="<?php echo h($edit['title'] ?? ''); ?>">
						</div>
						<div class="mb-2">
							<label class="form-label">Category</label>
							<input class="form-control form-control-sm" name="category" required value="<?php echo h($edit['category'] ?? ''); ?>">
						</div>
						<div class="mb-2">
							<label class="form-label">Severity</label>
							<select class="form-select form-select-sm" name="severity" id="severitySelect">
								<?php 
								$severity_colors = ['low' => 'secondary', 'medium' => 'warning', 'high' => 'danger'];
								foreach (['low','medium','high'] as $s): 
									$color = $severity_colors[$s];
								?>
									<option value="<?php echo $s; ?>" <?php echo (($edit['severity'] ?? '')===$s)?'selected':''; ?> data-color="<?php echo $color; ?>">
										<?php echo ucfirst($s); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div class="mt-2">
								<span id="severityPreview" class="badge text-bg-<?php 
									$current_severity = $edit['severity'] ?? 'low';
									echo $current_severity === 'high' ? 'danger' : ($current_severity === 'medium' ? 'warning' : 'secondary');
								?>">
									Current: <?php echo ucfirst($current_severity); ?>
								</span>
							</div>
						</div>
						<div class="mb-2">
							<label class="form-label">Description</label>
							<textarea class="form-control form-control-sm" name="description" rows="3"><?php echo h($edit['description'] ?? ''); ?></textarea>
						</div>
						<div class="d-grid">
							<button class="btn btn-primary btn-sm" type="submit"><?php echo $edit ? 'Update' : 'Add'; ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
// Update severity preview when selection changes
document.addEventListener('DOMContentLoaded', function() {
	const severitySelect = document.getElementById('severitySelect');
	const severityPreview = document.getElementById('severityPreview');
	
	if (severitySelect && severityPreview) {
		severitySelect.addEventListener('change', function() {
			const selectedOption = this.options[this.selectedIndex];
			const severity = selectedOption.value;
			const color = selectedOption.getAttribute('data-color');
			
			severityPreview.className = 'badge text-bg-' + color;
			severityPreview.textContent = 'Current: ' + severity.charAt(0).toUpperCase() + severity.slice(1);
		});
	}
});
</script>

<?php include __DIR__ . '/footer.php'; ?>

