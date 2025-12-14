<?php
require_once __DIR__ . '/config.php';

// Ensure default users exist (first-run bootstrap)
try {
	$db_boot = get_db_connection();
	$cntRes = $db_boot->query('SELECT COUNT(*) AS c FROM users');
	if ($cntRes) {
		$count = (int)$cntRes->fetch_assoc()['c'];
		if ($count === 0) {
			$adminHash = password_hash('admin123', PASSWORD_BCRYPT);
			$viewerHash = password_hash('student123', PASSWORD_BCRYPT);
			$stmt1 = $db_boot->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?,?,?,?)');
			$email = 'admin@example.com'; $name = 'Administrator'; $role = 'admin';
			$stmt1->bind_param('ssss', $email, $adminHash, $name, $role);
			$stmt1->execute();
			$emailV = 'student@viewer.com'; $nameV = 'Student'; $roleV = 'viewer';
			$stmtV = $db_boot->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?,?,?,?)');
			$stmtV->bind_param('ssss', $emailV, $viewerHash, $nameV, $roleV);
			$stmtV->execute();
			// Additional accounts
			$email3 = 'mattjhevicadmin@example.com'; $name3 = 'Matt Jhevic Admin'; $role3 = 'admin';
			$stmt3 = $db_boot->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?,?,?,?)');
			$stmt3->bind_param('ssss', $email3, $adminHash, $name3, $role3);
			$stmt3->execute();
		}
	}
} catch (Throwable $e) {
	// ignore bootstrap errors; login flow will still work if users already exist
}

try {
	$db_cleanup = get_db_connection();
	$db_cleanup->query("DELETE FROM users WHERE role='staff' OR email IN ('staff@example.com','mattjhevicstaff@example.com')");
} catch (Throwable $e) {
}

// Optional manual seed/reset via query: ?seed=1
if (($_GET['seed'] ?? '') === '1') {
	try {
		$db_seed = get_db_connection();
		$adminHash = password_hash('admin123', PASSWORD_BCRYPT);
		$viewerHash = password_hash('student123', PASSWORD_BCRYPT);
		$stmt = $db_seed->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), full_name=VALUES(full_name), role=VALUES(role)');
		$email = 'admin@example.com'; $name = 'Administrator'; $role = 'admin';
		$stmt->bind_param('ssss', $email, $adminHash, $name, $role);
		$stmt->execute();
		$emailV = 'student@viewer.com'; $nameV = 'Student'; $roleV = 'viewer';
		$stmt->bind_param('ssss', $emailV, $viewerHash, $nameV, $roleV);
		$stmt->execute();
		// Additional accounts
		$email3 = 'mattjhevicadmin@example.com'; $name3 = 'Mattjhevic Admin'; $role3 = 'admin';
		$stmt->bind_param('ssss', $email3, $adminHash, $name3, $role3);
		$stmt->execute();
		$_SESSION['flash'] = 'Default accounts reset. Use admin@example.com / admin123, student@viewer.com / student123';
	} catch (Throwable $e) {
		$_SESSION['flash'] = 'Seeding failed: ' . $e->getMessage();
	}
	header('Location: index.php');
	exit;
}

$error = '';
if (isset($_SESSION['user'])) {
	$role = ($_SESSION['user']['role'] ?? '');
	$dest = $role === 'student' ? 'student.php' : ($role === 'viewer' ? 'viewer.php' : 'dashboard.php');
	header('Location: ' . $dest);
	exit;
}

if (is_post()) {
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$db = get_db_connection();
	$stmt = $db->prepare('SELECT id, email, password_hash, full_name, role FROM users WHERE email = ?');
	$stmt->bind_param('s', $email);
	$stmt->execute();
	$res = $stmt->get_result();
	$user = $res->fetch_assoc();
	if ($user && password_verify($password, $user['password_hash'])) {
		$_SESSION['user'] = [
			'id' => $user['id'],
			'email' => $user['email'],
			'full_name' => $user['full_name'],
			'role' => $user['role'],
		];
		$dest = ($user['role'] === 'student') ? 'student.php' : (($user['role'] === 'viewer') ? 'viewer.php' : 'dashboard.php');
		header('Location: ' . $dest);
		exit;
	} else {
		$error = 'Invalid credentials';
	}
}
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="container container-narrow">
	<div class="row justify-content-center align-items-center" style="min-height: 80vh;">
		<!-- Login Form -->
		<div class="col-md-5">
			<div class="card shadow-lg border-0">
				<div class="card-body p-5">
					<div class="text-center mb-4">
						<div class="mb-3" style="font-size: 4rem;">🛡️</div>
						<h3 class="fw-bold mb-2">Student Violation Monitoring System</h3>
						<p class="text-muted">Sign in to your account</p>
					</div>
					<?php if ($error): ?>
						<div class="alert alert-danger py-2"><?php echo h($error); ?></div>
					<?php endif; ?>
					<?php if (!empty($_SESSION['flash'])): ?>
						<div class="alert alert-info py-2"><?php echo h($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
					<?php endif; ?>
					<form method="post" novalidate>
						<div class="mb-3">
							<label class="form-label fw-semibold"><i class="bi bi-envelope"></i> Email Address</label>
							<input type="email" name="email" class="form-control form-control-lg" placeholder="Enter your email" required autofocus>
						</div>
						<div class="mb-4">
							<label class="form-label fw-semibold"><i class="bi bi-lock"></i> Password</label>
							<input type="password" name="password" class="form-control form-control-lg" placeholder="Enter your password" required>
						</div>
						<button class="btn btn-primary btn-lg w-100 fw-semibold" type="submit">
							<i class="bi bi-box-arrow-in-right"></i> Sign In
						</button>
					</form>
					<div class="text-center mt-4">
						<a href="index.php?seed=1" class="small text-muted text-decoration-none d-block">
							<i class="bi bi-arrow-clockwise"></i> Reset default accounts
						</a>
						<a href="register.php" class="small text-primary text-decoration-none mt-2 d-block">
							<i class="bi bi-person-plus"></i> Create an account
						</a>
					</div>
				</div>
			</div>
			<div class="text-center mt-4">
				<p class="text-muted small">
					<i class="bi bi-shield-check"></i> Secure Login System
				</p>
			</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
