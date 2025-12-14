<?php
require_once __DIR__ . '/auth.php';
require_role('admin');
$db = get_db_connection();
$hasUserId = false;
try {
    $colRes = $db->query("SHOW COLUMNS FROM students LIKE 'user_id'");
    $hasUserId = $colRes && $colRes->num_rows > 0;
} catch (Throwable $e) {
    $hasUserId = false;
}

// Create / Update
$message = '';
if (is_post()) {
	$action = $_POST['action'] ?? '';
	if ($action === 'create') {
		$student_no = trim($_POST['student_no'] ?? '');
		$first_name = trim($_POST['first_name'] ?? '');
		$last_name = trim($_POST['last_name'] ?? '');
		$class = trim($_POST['class'] ?? '');
		$section = trim($_POST['section'] ?? '');
		$guardian_contact = trim($_POST['guardian_contact'] ?? '');
		$login_email = trim($_POST['login_email'] ?? '');
		$login_password = $_POST['login_password'] ?? '';
		$stmt = $db->prepare('INSERT INTO students (student_no, first_name, last_name, class, section, guardian_contact) VALUES (?,?,?,?,?,?)');
		$stmt->bind_param('ssssss', $student_no, $first_name, $last_name, $class, $section, $guardian_contact);
        if ($stmt->execute()) { 
			$studentId = $stmt->insert_id;
            if ($login_email !== '' && $hasUserId) {
				$hash = $login_password !== '' ? password_hash($login_password, PASSWORD_BCRYPT) : password_hash('student123', PASSWORD_BCRYPT);
				$ins = $db->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?,?,?,\'student\') ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), full_name=VALUES(full_name), role=VALUES(role)');
				$fullName = $first_name . ' ' . $last_name;
				$ins->bind_param('sss', $login_email, $hash, $fullName);
				$ins->execute();
				$userId = $db->insert_id ?: $db->query("SELECT id FROM users WHERE email='" . $db->real_escape_string($login_email) . "' LIMIT 1")->fetch_assoc()['id'];
				$updLink = $db->prepare('UPDATE students SET user_id=? WHERE id=?');
				$updLink->bind_param('ii', $userId, $studentId);
				$updLink->execute();
			}
			$message = 'Student added'; 
		}
	}
	if ($action === 'update') {
		$id = (int)($_POST['id'] ?? 0);
		$student_no = trim($_POST['student_no'] ?? '');
		$first_name = trim($_POST['first_name'] ?? '');
		$last_name = trim($_POST['last_name'] ?? '');
		$class = trim($_POST['class'] ?? '');
		$section = trim($_POST['section'] ?? '');
		$guardian_contact = trim($_POST['guardian_contact'] ?? '');
		$login_email = trim($_POST['login_email'] ?? '');
		$login_password = $_POST['login_password'] ?? '';
		$stmt = $db->prepare('UPDATE students SET student_no=?, first_name=?, last_name=?, class=?, section=?, guardian_contact=? WHERE id=?');
		$stmt->bind_param('ssssssi', $student_no, $first_name, $last_name, $class, $section, $guardian_contact, $id);
        if ($stmt->execute()) { 
            if ($login_email !== '' && $hasUserId) {
				$hash = $login_password !== '' ? password_hash($login_password, PASSWORD_BCRYPT) : null;
				if ($hash) {
					$ins = $db->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?,?,?,\'student\') ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), full_name=VALUES(full_name), role=VALUES(role)');
					$fullName = $first_name . ' ' . $last_name;
					$ins->bind_param('sss', $login_email, $hash, $fullName);
					$ins->execute();
				} else {
					$ins = $db->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?,?,?,\'student\') ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), role=VALUES(role)');
					$dummy = password_hash('student123', PASSWORD_BCRYPT); // used only for insert path
					$fullName = $first_name . ' ' . $last_name;
					$ins->bind_param('sss', $login_email, $dummy, $fullName);
					$ins->execute();
				}
				$userId = $db->insert_id ?: $db->query("SELECT id FROM users WHERE email='" . $db->real_escape_string($login_email) . "' LIMIT 1")->fetch_assoc()['id'];
				$updLink = $db->prepare('UPDATE students SET user_id=? WHERE id=?');
				$updLink->bind_param('ii', $userId, $id);
				$updLink->execute();
			}
			$message = 'Student updated'; 
		}
	}
}

// Delete
if (($_GET['delete'] ?? '') !== '') {
	$id = (int)$_GET['delete'];
	$db->query('DELETE FROM students WHERE id=' . $id);
	header('Location: students.php');
	exit;
}

// Search
$q = trim($_GET['q'] ?? '');
$where = '';
if ($q !== '') {
	$qLike = '%' . $db->real_escape_string($q) . '%';
	$where = "WHERE student_no LIKE '$qLike' OR first_name LIKE '$qLike' OR last_name LIKE '$qLike'";
}
$students = $db->query("SELECT * FROM students $where ORDER BY last_name, first_name LIMIT 200");

// For edit form
$edit = null;
if (($_GET['edit'] ?? '') !== '') {
	$id = (int)$_GET['edit'];
$res = $db->query('SELECT * FROM students WHERE id=' . $id);
	$edit = $res->fetch_assoc();
if ($edit && !empty($edit['user_id'])) {
	$u = $db->query('SELECT email FROM users WHERE id=' . (int)$edit['user_id'])->fetch_assoc();
	$edit['login_email'] = $u['email'] ?? '';
}
}
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/nav.php'; ?>
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h4 class="mb-0">Students</h4>
		<form class="d-flex" method="get">
			<input type="text" name="q" class="form-control form-control-sm me-2" placeholder="Search..." value="<?php echo h($q); ?>">
			<button class="btn btn-sm btn-outline-secondary">Search</button>
		</form>
	</div>

	<?php if ($message): ?><div class="alert alert-success py-2"><?php echo h($message); ?></div><?php endif; ?>

	<div class="row g-3">
		<div class="col-md-7">
			<div class="card">
				<div class="table-responsive">
					<table class="table table-sm mb-0">
						<thead><tr><th>#</th><th>Student No</th><th>Name</th><th>Class</th><th>Section</th><th></th></tr></thead>
						<tbody>
							<?php while ($row = $students->fetch_assoc()): ?>
								<tr>
									<td><?php echo $row['id']; ?></td>
									<td><?php echo h($row['student_no']); ?></td>
									<td><?php echo h($row['last_name'] . ', ' . $row['first_name']); ?></td>
									<td><?php echo h($row['class']); ?></td>
									<td><?php echo h($row['section']); ?></td>
									<td class="text-end">
										<a class="btn btn-sm btn-outline-primary" href="?edit=<?php echo $row['id']; ?>">Edit</a>
										<a class="btn btn-sm btn-outline-danger" href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this student?')">Delete</a>
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
				<div class="card-header"><?php echo $edit ? 'Edit Student' : 'Add Student'; ?></div>
				<div class="card-body">
					<form method="post">
						<input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'create'; ?>">
						<?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
						<div class="mb-2">
							<label class="form-label">Student No</label>
							<input class="form-control form-control-sm" name="student_no" required value="<?php echo h($edit['student_no'] ?? ''); ?>">
						</div>
						<div class="row">
							<div class="col-md-6 mb-2">
								<label class="form-label">First Name</label>
								<input class="form-control form-control-sm" name="first_name" required value="<?php echo h($edit['first_name'] ?? ''); ?>">
							</div>
							<div class="col-md-6 mb-2">
								<label class="form-label">Last Name</label>
								<input class="form-control form-control-sm" name="last_name" required value="<?php echo h($edit['last_name'] ?? ''); ?>">
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-2">
								<label class="form-label">Class</label>
								<input class="form-control form-control-sm" name="class" value="<?php echo h($edit['class'] ?? ''); ?>">
							</div>
							<div class="col-md-6 mb-2">
								<label class="form-label">Section</label>
								<input class="form-control form-control-sm" name="section" value="<?php echo h($edit['section'] ?? ''); ?>">
							</div>
						</div>
						<div class="mb-2">
							<label class="form-label">Guardian Contact</label>
							<input class="form-control form-control-sm" name="guardian_contact" value="<?php echo h($edit['guardian_contact'] ?? ''); ?>">
						</div>
					<hr>
					<div class="mb-2">
						<label class="form-label">Student Login Email</label>
						<input class="form-control form-control-sm" name="login_email" placeholder="email@example.com" value="<?php echo h($edit['login_email'] ?? ''); ?>">
						<div class="form-text">Optional. Creates/links a user with role student.</div>
					</div>
					<div class="mb-2">
						<label class="form-label">Student Login Password</label>
						<input class="form-control form-control-sm" name="login_password" type="password" placeholder="Leave blank to keep or set default">
						<div class="form-text">If blank when creating, default password is student123.</div>
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
<?php include __DIR__ . '/footer.php'; ?>

