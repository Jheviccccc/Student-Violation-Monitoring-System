<?php
require_once __DIR__ . '/config.php';

// If already logged in, redirect
if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';
    $dest = $role === 'student' ? 'student.php' : ($role === 'viewer' ? 'viewer.php' : 'dashboard.php');
    header('Location: ' . $dest);
    exit;
}

$error = '';
$success = '';
if (is_post()) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($full_name === '' || $email === '' || $password === '' || $password2 === '') {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db = get_db_connection();
        // Check existing
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $error = 'An account with that email already exists. <a href="index.php">Sign in</a>.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            // Allow selecting role on registration, default to 'student'
            $allowed_roles = ['student', 'admin'];
            $posted_role = strtolower(trim($_POST['role'] ?? 'student'));
            $role = in_array($posted_role, $allowed_roles, true) ? $posted_role : 'student';

            $ins = $db->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?,?,?,?)');
            $ins->bind_param('ssss', $email, $hash, $full_name, $role);
            if ($ins->execute()) {
                $userId = $db->insert_id;
                // Auto-create a student record linking to this user only for student role
                if ($role === 'student') {
                    $stmtS = $db->prepare('INSERT INTO students (student_no, first_name, last_name, user_id) VALUES (?, ?, ?, ?)');
                    // Create a placeholder student_no using user id
                    $student_no = 'S' . str_pad($userId, 6, '0', STR_PAD_LEFT);
                    // Try to split name
                    $parts = explode(' ', $full_name, 2);
                    $first = $parts[0] ?? $full_name;
                    $last = $parts[1] ?? '';
                    $stmtS->bind_param('sssi', $student_no, $first, $last, $userId);
                    @$stmtS->execute(); // ignore if students table not present or unique constraint
                }

                // Log user in
                $_SESSION['user'] = [
                    'id' => $userId,
                    'email' => $email,
                    'full_name' => $full_name,
                    'role' => $role,
                ];
                // Redirect based on role
                if ($role === 'student') {
                    header('Location: student.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            } else {
                $error = 'Failed to create account. Please try again.';
            }
        }
    }
}
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="container container-narrow">
    <div class="row justify-content-center" style="min-height:70vh;">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <h4 class="mb-3">Create an account</h4>
                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success py-2"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Full name</label>
                            <input type="text" name="full_name" class="form-control" required value="<?php echo h($_POST['full_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo h($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm password</label>
                            <input type="password" name="password2" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Register as</label>
                            <select name="role" class="form-select">
                                <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <div class="form-text">Choose account type. Admin accounts grant dashboard access.</div>
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-primary" type="submit">Create account</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <small>Already have an account? <a href="index.php">Sign in</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
