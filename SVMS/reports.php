<?php
require_once __DIR__ . '/auth.php';
require_role('admin');
$db = get_db_connection();

// Get filter parameters
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$student_q = trim($_GET['student'] ?? '');
$violation_q = trim($_GET['violation'] ?? '');
$category = $_GET['category'] ?? '';
$severity = $_GET['severity'] ?? '';
$class = $_GET['class'] ?? '';
$export = ($_GET['export'] ?? '') === 'csv';
$print = ($_GET['print'] ?? '') === '1';
// Sort option: name A-Z / Z-A or date
$name_sort = $_GET['name_sort'] ?? '';

// Build conditions
$conditions = ["vr.occurred_at BETWEEN ? AND ?"];
$params = [$from, $to];
$types = 'ss';

if ($student_q !== '') { 
    $conditions[] = "(s.student_no LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)"; 
    $params[] = "%$student_q%"; 
    $params[] = "%$student_q%"; 
    $params[] = "%$student_q%"; 
    $types .= 'sss'; 
}
if ($violation_q !== '') { 
    $conditions[] = "(v.title LIKE ? OR v.description LIKE ?)"; 
    $params[] = "%$violation_q%"; 
    $params[] = "%$violation_q%"; 
    $types .= 'ss'; 
}
if ($category !== '') { 
    $conditions[] = "v.category = ?"; 
    $params[] = $category; 
    $types .= 's'; 
}
if ($severity !== '') { 
    $conditions[] = "v.severity = ?"; 
    $params[] = $severity; 
    $types .= 's'; 
}
if ($class !== '') { 
    $conditions[] = "s.class LIKE ?"; 
    $params[] = "%$class%"; 
    $types .= 's'; 
}

// Get summary statistics
$summary_sql = 'SELECT 
    COUNT(vr.id) as total_records,
    COUNT(DISTINCT vr.student_id) as unique_students,
    COUNT(CASE WHEN v.severity = "high" THEN 1 END) as high_severity,
    COUNT(CASE WHEN v.severity = "medium" THEN 1 END) as medium_severity,
    COUNT(CASE WHEN v.severity = "low" THEN 1 END) as low_severity
  FROM violation_records vr
  JOIN students s ON s.id = vr.student_id
  JOIN violations v ON v.id = vr.violation_id
  WHERE ' . implode(' AND ', $conditions);

$summary_stmt = $db->prepare($summary_sql);
$summary_stmt->bind_param($types, ...$params);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

// Get detailed records
// Determine ordering
$order_by = 'vr.occurred_at DESC';
if ($name_sort === 'az') {
	$order_by = 's.last_name ASC, s.first_name ASC';
} elseif ($name_sort === 'za') {
	$order_by = 's.last_name DESC, s.first_name DESC';
} elseif ($name_sort === 'date_asc') {
	$order_by = 'vr.occurred_at ASC';
}

$sql = 'SELECT vr.id, vr.occurred_at, vr.disposition, vr.notes,
    s.student_no, s.first_name, s.last_name, s.class, s.section,
    v.title, v.category, v.severity, v.description
  FROM violation_records vr
  JOIN students s ON s.id = vr.student_id
  JOIN violations v ON v.id = vr.violation_id
	WHERE ' . implode(' AND ', $conditions) . ' ORDER BY ' . $order_by;

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Handle exports
if ($export) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="svms_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date','Student No','First Name','Last Name','Class','Section','Violation','Category','Severity','Description','Disposition','Notes']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row['occurred_at'],
            $row['student_no'],
            $row['first_name'],
            $row['last_name'],
            $row['class'],
            $row['section'],
            $row['title'],
            $row['category'],
            $row['severity'],
            $row['description'],
            $row['disposition'],
            $row['notes']
        ]);
    }
    fclose($out);
    exit;
}

// Get filter options for dropdowns
$categories = $db->query('SELECT DISTINCT category FROM violations ORDER BY category');
$classes = $db->query('SELECT DISTINCT class FROM students WHERE class IS NOT NULL ORDER BY class');

// Handle print view
if ($print) {
    include __DIR__ . '/header.php';
    ?>
    <div class="container print-view">
        <div class="print-header">
            <h2>Student Violation Monitoring System - Report</h2>
            <p>Generated on <?php echo date('F j, Y g:i A'); ?></p>
        </div>
        
        <div class="report-summary mb-4">
            <h4>Summary Statistics</h4>
            <div class="row">
                <div class="col-md-3">
                    <strong>Total Records:</strong> <?php echo $summary['total_records']; ?>
                </div>
                <div class="col-md-3">
                    <strong>Unique Students:</strong> <?php echo $summary['unique_students']; ?>
                </div>
                <div class="col-md-3">
                    <strong>High Severity:</strong> <?php echo $summary['high_severity']; ?>
                </div>
                <div class="col-md-3">
                    <strong>Date Range:</strong> <?php echo date('M j, Y', strtotime($from)); ?> - <?php echo date('M j, Y', strtotime($to)); ?>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Student No</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Violation</th>
                        <th>Category</th>
                        <th>Severity</th>
                        <th>Disposition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo h($row['occurred_at']); ?></td>
                            <td><?php echo h($row['student_no']); ?></td>
                            <td><?php echo h($row['last_name'] . ', ' . $row['first_name']); ?></td>
                            <td><?php echo h($row['class'] . ' - ' . $row['section']); ?></td>
                            <td><?php echo h($row['title']); ?></td>
                            <td><?php echo h($row['category']); ?></td>
                            <td><span class="badge bg-<?php echo $row['severity']==='high'?'danger':($row['severity']==='medium'?'warning':'secondary'); ?>"><?php echo h($row['severity']); ?></span></td>
                            <td><?php echo h($row['disposition'] ?? 'Pending'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/nav.php'; ?>
<div class="container">
	<h4 class="mb-3">Comprehensive Reports</h4>
	
	<!-- Summary Statistics -->
	<div class="report-summary">
		<h5>Summary Statistics</h5>
		<div class="row">
			<div class="col-md-3">
				<div class="text-center">
					<h3 class="text-primary"><?php echo $summary['total_records']; ?></h3>
					<small class="text-muted">Total Records</small>
				</div>
			</div>
			<div class="col-md-3">
				<div class="text-center">
					<h3 class="text-info"><?php echo $summary['unique_students']; ?></h3>
					<small class="text-muted">Unique Students</small>
				</div>
			</div>
			<div class="col-md-2">
				<div class="text-center">
					<h3 class="text-danger"><?php echo $summary['high_severity']; ?></h3>
					<small class="text-muted">High Severity</small>
				</div>
			</div>
			<div class="col-md-2">
				<div class="text-center">
					<h3 class="text-warning"><?php echo $summary['medium_severity']; ?></h3>
					<small class="text-muted">Medium Severity</small>
				</div>
			</div>
			<div class="col-md-2">
				<div class="text-center">
					<h3 class="text-secondary"><?php echo $summary['low_severity']; ?></h3>
					<small class="text-muted">Low Severity</small>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Enhanced Filters -->
	<div class="report-filters">
		<h5>Filter Options</h5>
		<form class="row g-3" method="get">
			<div class="col-md-3">
				<label class="form-label">From Date</label>
				<input type="date" class="form-control" name="from" value="<?php echo h($from); ?>">
			</div>
			<div class="col-md-3">
				<label class="form-label">To Date</label>
				<input type="date" class="form-control" name="to" value="<?php echo h($to); ?>">
			</div>
			<div class="col-md-3">
				<label class="form-label">Student (No/Name)</label>
				<input class="form-control" name="student" placeholder="Student number or name" value="<?php echo h($student_q); ?>">
			</div>
			<div class="col-md-3">
				<label class="form-label">Class</label>
				<select class="form-select" name="class">
					<option value="">All Classes</option>
					<?php while ($class_row = $classes->fetch_assoc()): ?>
						<option value="<?php echo h($class_row['class']); ?>" <?php echo $class === $class_row['class'] ? 'selected' : ''; ?>>
							<?php echo h($class_row['class']); ?>
						</option>
					<?php endwhile; ?>
				</select>
			</div>
			<div class="col-md-3">
				<label class="form-label">Violation</label>
				<input class="form-control" name="violation" placeholder="Violation title" value="<?php echo h($violation_q); ?>">
			</div>
			<div class="col-md-3">
				<label class="form-label">Category</label>
				<select class="form-select" name="category">
					<option value="">All Categories</option>
					<?php while ($cat_row = $categories->fetch_assoc()): ?>
						<option value="<?php echo h($cat_row['category']); ?>" <?php echo $category === $cat_row['category'] ? 'selected' : ''; ?>>
							<?php echo h($cat_row['category']); ?>
						</option>
					<?php endwhile; ?>
				</select>
			</div>
			<div class="col-md-3">
				<label class="form-label">Severity</label>
				<select class="form-select" name="severity">
					<option value="">All Severities</option>
					<option value="high" <?php echo $severity === 'high' ? 'selected' : ''; ?>>High</option>
					<option value="medium" <?php echo $severity === 'medium' ? 'selected' : ''; ?>>Medium</option>
					<option value="low" <?php echo $severity === 'low' ? 'selected' : ''; ?>>Low</option>
				</select>
			</div>
			<div class="col-md-3">
				<label class="form-label">Sort By</label>
				<select class="form-select" name="name_sort">
					<option value="">Date (Newest)</option>
					<option value="date_asc" <?php echo $name_sort === 'date_asc' ? 'selected' : ''; ?>>Date (Oldest)</option>
					<option value="az" <?php echo $name_sort === 'az' ? 'selected' : ''; ?>>Name A - Z</option>
					<option value="za" <?php echo $name_sort === 'za' ? 'selected' : ''; ?>>Name Z - A</option>
				</select>
			</div>
			<div class="col-md-3 d-flex align-items-end">
				<button class="btn btn-primary me-2" type="submit">Apply Filters</button>
				<a href="reports.php" class="btn btn-secondary me-2">Reset</a>
			</div>
		</form>
		
		<!-- Action Buttons -->
		<div class="row mt-3">
			<div class="col-12">
				<div class="btn-group" role="group">
              <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i> Export CSV
              </a>
              <button id="download-pdf" class="btn btn-outline-danger">
                <i class="bi bi-file-earmark-pdf"></i> Download as PDF
              </button>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['print' => '1'])); ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> Print Report
              </a>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Detailed Results -->
	<div class="card">
		<div class="card-header d-flex justify-content-between align-items-center d-print-none">
			<h5 class="mb-0">Detailed Results (<?php echo $result->num_rows; ?> records)</h5>
			<small class="text-muted">Filtered by: <?php echo date('M j, Y', strtotime($from)); ?> - <?php echo date('M j, Y', strtotime($to)); ?></small>
		</div>
		<div class="table-responsive" id="report-content">
			<div class="print-header d-none d-print-block">
				<h2>Student Violation Monitoring System - Report</h2>
				<p>Generated on <?php echo date('F j, Y g:i A'); ?></p>
				<div class="row">
					<div class="col">
						<strong>Total Records:</strong> <?php echo $summary['total_records']; ?>
					</div>
					<div class="col">
						<strong>Unique Students:</strong> <?php echo $summary['unique_students']; ?>
					</div>
					<div class="col">
						<strong>High Severity:</strong> <?php echo $summary['high_severity']; ?>
					</div>
					<div class="col">
						<strong>Date Range:</strong> <?php echo date('M j, Y', strtotime($from)); ?> - <?php echo date('M j, Y', strtotime($to)); ?>
					</div>
				</div>
			</div>
			<table class="table table-sm table-hover table-bordered mb-0">
				<thead class="table-light">
					<tr>
						<th>Date</th>
						<th>Student No</th>
						<th>Name</th>
						<th>Class</th>
						<th>Violation</th>
						<th>Category</th>
						<th>Severity</th>
						<th>Disposition</th>
						<th class="d-print-none">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($row = $result->fetch_assoc()): ?>
						<tr>
							<td><?php echo h($row['occurred_at']); ?></td>
							<td><?php echo h($row['student_no']); ?></td>
							<td><?php echo h($row['last_name'] . ', ' . $row['first_name']); ?></td>
							<td><?php echo h($row['class'] . ' - ' . $row['section']); ?></td>
							<td><?php echo h($row['title']); ?></td>
							<td><?php echo h($row['category']); ?></td>
							<td>
								<span class="badge text-bg-<?php echo $row['severity']==='high'?'danger':($row['severity']==='medium'?'warning':'secondary'); ?> severity-<?php echo $row['severity']; ?>">
									<?php echo ucfirst(h($row['severity'])); ?>
								</span>
							</td>
							<td><?php echo h($row['disposition'] ?? 'Pending'); ?></td>
							<td class="d-print-none">
								<a href="records.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<script>
document.getElementById('download-pdf').addEventListener('click', function () {
    const element = document.getElementById('report-content');
    const printHeader = element.querySelector('.print-header');
    
	printHeader.classList.remove('d-none');

	const opt = {
		margin:       0.4,
		filename:     'svms_report_<?php echo date('Y-m-d'); ?>.pdf',
		image:        { type: 'jpeg', quality: 0.98 },
		html2canvas:  { scale: 2, useCORS: true },
		pagebreak:    { mode: ['css'] },
		jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
	};

    html2pdf().set(opt).from(element).save().then(() => {
		printHeader.classList.add('d-none');
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>

