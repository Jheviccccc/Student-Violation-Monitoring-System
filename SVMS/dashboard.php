<?php
require_once __DIR__ . '/auth.php';
require_role('admin');
$db = get_db_connection();

// Current month and year for calculations
$current_month = date('Y-m');
$current_year = date('Y');
$last_month = date('Y-m', strtotime('-1 month'));

// Weekly calculations (last 7 days)
$week_start = date('Y-m-d', strtotime('-7 days'));
$week_end = date('Y-m-d');
$violations_this_week = (int)$db->query("SELECT COUNT(*) AS c FROM violation_records WHERE occurred_at >= '$week_start' AND occurred_at <= '$week_end'")->fetch_assoc()['c'] ?? 0;

// Basic Counts
$students_count = (int)$db->query('SELECT COUNT(*) AS c FROM students')->fetch_assoc()['c'] ?? 0;
$violations_count = (int)$db->query('SELECT COUNT(*) AS c FROM violations')->fetch_assoc()['c'] ?? 0;
$records_count = (int)$db->query('SELECT COUNT(*) AS c FROM violation_records')->fetch_assoc()['c'] ?? 0;

// Monthly Statistics
$current_month_records = (int)$db->query("SELECT COUNT(*) AS c FROM violation_records WHERE DATE_FORMAT(occurred_at, '%Y-%m') = '$current_month'")->fetch_assoc()['c'] ?? 0;
$last_month_records = (int)$db->query("SELECT COUNT(*) AS c FROM violation_records WHERE DATE_FORMAT(occurred_at, '%Y-%m') = '$last_month'")->fetch_assoc()['c'] ?? 0;

// Calculate monthly trend
$monthly_trend = $last_month_records > 0 ? round((($current_month_records - $last_month_records) / $last_month_records) * 100, 1) : 0;

// Top 3 Violations by frequency (store results)
$top_3_violations_result = $db->query('SELECT v.title, v.category, v.severity, COUNT(vr.id) as count 
  FROM violations v 
  LEFT JOIN violation_records vr ON v.id = vr.violation_id 
  GROUP BY v.id 
  ORDER BY count DESC 
  LIMIT 3');
$top_3_violations_data = [];
while ($row = $top_3_violations_result->fetch_assoc()) {
    $top_3_violations_data[] = $row;
}

// Top Violations by frequency (for other sections)
$top_violations = $db->query('SELECT v.title, v.category, v.severity, COUNT(vr.id) as count 
  FROM violations v 
  LEFT JOIN violation_records vr ON v.id = vr.violation_id 
  GROUP BY v.id 
  ORDER BY count DESC 
  LIMIT 5');

// Incident trends - Last 7 days
$trend_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime("-$i days"));
    $count = (int)$db->query("SELECT COUNT(*) AS c FROM violation_records WHERE DATE(occurred_at) = '$date'")->fetch_assoc()['c'] ?? 0;
    $trend_data[] = [
        'day' => $day_name,
        'date' => date('M j', strtotime("-$i days")),
        'count' => $count
    ];
}

// Violations by severity
$severity_stats = $db->query('SELECT v.severity, COUNT(vr.id) as count 
  FROM violations v 
  LEFT JOIN violation_records vr ON v.id = vr.violation_id 
  GROUP BY v.severity 
  ORDER BY count DESC');

// Students with most violations
$top_students = $db->query('SELECT s.student_no, s.first_name, s.last_name, COUNT(vr.id) as violation_count 
  FROM students s 
  LEFT JOIN violation_records vr ON s.id = vr.student_id 
  GROUP BY s.id 
  HAVING violation_count > 0 
  ORDER BY violation_count DESC 
  LIMIT 5');

// Recent 5 records
$recent = $db->query('SELECT vr.id, vr.occurred_at, s.student_no, s.first_name, s.last_name, v.title, v.severity
  FROM violation_records vr
  JOIN students s ON s.id = vr.student_id
  JOIN violations v ON v.id = vr.violation_id
  ORDER BY vr.created_at DESC
  LIMIT 5');

// Pending dispositions (records without disposition)
$pending_dispositions = (int)$db->query("SELECT COUNT(*) AS c FROM violation_records WHERE disposition IS NULL OR disposition = ''")->fetch_assoc()['c'] ?? 0;
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/nav.php'; ?>
<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<div>
			<h2 class="fw-bold mb-1"><i class="bi bi-speedometer2 text-primary"></i> Dashboard Overview</h2>
			<p class="text-muted mb-0">Monitor and analyze student violations at a glance</p>
		</div>
		<div class="text-end">
			<div class="text-muted small">
				<i class="bi bi-calendar3"></i> <?php echo date('F j, Y'); ?>
			</div>
			<div class="text-muted small">
				<i class="bi bi-clock"></i> <?php echo date('g:i A'); ?>
			</div>
		</div>
	</div>

	<!-- Quick Stats Carousel -->
	<div class="row mb-4">
		<div class="col-12">
			<div id="statsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
				<div class="carousel-inner">
					<div class="carousel-item active">
						<div class="card border-0 shadow-sm carousel-content variant-1">
							<div class="card-body p-4">
								<div class="row align-items-center">
									<div class="col-md-8">
										<h4 class="fw-bold mb-2"><i class="bi bi-clipboard-data"></i> System Overview</h4>
										<p class="mb-0">Total of <strong><?php echo $students_count; ?></strong> students, <strong><?php echo $violations_count; ?></strong> violation types, and <strong><?php echo $records_count; ?></strong> records tracked in the system.</p>
									</div>
									<div class="col-md-4 text-end">
										<div style="font-size: 4rem; opacity: 0.3;">📊</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="carousel-item">
						<div class="card border-0 shadow-sm carousel-content variant-2">
							<div class="card-body p-4">
								<div class="row align-items-center">
									<div class="col-md-8">
										<h4 class="fw-bold mb-2"><i class="bi bi-calendar-week"></i> This Week's Activity</h4>
										<p class="mb-0"><strong><?php echo $violations_this_week; ?></strong> violations recorded this week. Monitor trends and patterns to improve student behavior.</p>
									</div>
									<div class="col-md-4 text-end">
										<div style="font-size: 4rem; opacity: 0.3;">📅</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="carousel-item">
						<div class="card border-0 shadow-sm carousel-content variant-3">
							<div class="card-body p-4">
								<div class="row align-items-center">
									<div class="col-md-8">
										<h4 class="fw-bold mb-2"><i class="bi bi-clock-history"></i> Pending Actions</h4>
										<p class="mb-0">You have <strong><?php echo $pending_dispositions; ?></strong> violation records pending disposition. Review and update them to maintain accurate records.</p>
									</div>
									<div class="col-md-4 text-end">
										<div style="font-size: 4rem; opacity: 0.3;">⏰</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<button class="carousel-control-prev" type="button" data-bs-target="#statsCarousel" data-bs-slide="prev">
					<span class="carousel-control-prev-icon" aria-hidden="true"></span>
					<span class="visually-hidden">Previous</span>
				</button>
				<button class="carousel-control-next" type="button" data-bs-target="#statsCarousel" data-bs-slide="next">
					<span class="carousel-control-next-icon" aria-hidden="true"></span>
					<span class="visually-hidden">Next</span>
				</button>
			</div>
		</div>
	</div>

	<!-- Featured Statistics Row -->
	<div class="row g-4 mb-4">
		<!-- Total Violations This Week -->
		<div class="col-lg-4">
			<div class="stat-hero">
				<div class="card-body p-4">
					<div class="d-flex justify-content-between align-items-start mb-3">
						<div>
							<h6 class="text-white-50 mb-2 fw-semibold">
								<i class="bi bi-calendar-week"></i> Total Violations This Week
							</h6>
							<h1 class="display-3 fw-bold mb-0"><?php echo $violations_this_week; ?></h1>
							<p class="text-white-50 small mb-0 mt-2">
								<?php echo date('M j', strtotime($week_start)); ?> - <?php echo date('M j', strtotime($week_end)); ?>
							</p>
						</div>
						<div class="bg-white bg-opacity-20 rounded-circle p-3">
							<i class="bi bi-clipboard-data fs-1"></i>
						</div>
					</div>
					<div class="mt-3 pt-3 border-top border-white border-opacity-25">
						<a href="records.php" class="link-button fw-semibold d-block text-center">
							View All Records <i class="bi bi-arrow-right ms-1"></i>
						</a>
					</div>
				</div>
			</div>
		</div>

		<!-- Top 3 Infractions -->
		<div class="col-lg-8">
			<div class="card border-0 shadow-sm h-100">
				<div class="card-header bg-white border-0 pb-0">
					<div class="d-flex justify-content-between align-items-center">
						<h5 class="mb-0 fw-bold">
							<i class="bi bi-search text-primary"></i> Top 3 Infractions
						</h5>
						<a href="violations.php" class="btn btn-sm btn-outline-primary">
							<i class="bi bi-arrow-right"></i> View All
						</a>
					</div>
				</div>
				<div class="card-body">
					<?php 
					if (count($top_3_violations_data) > 0): 
						$rank = 1;
						foreach ($top_3_violations_data as $violation): 
							$severity_color = $violation['severity'] === 'high' ? 'danger' : ($violation['severity'] === 'medium' ? 'warning' : 'secondary');
					?>
						<div class="d-flex align-items-center mb-3 p-3 rounded" style="background: #f8f9fa;">
							<div class="me-3">
								<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">
									<?php echo $rank; ?>
								</div>
							</div>
							<div class="flex-grow-1">
								<h6 class="mb-1 fw-bold"><?php echo h($violation['title']); ?></h6>
								<div class="d-flex align-items-center gap-2">
									<span class="badge bg-<?php echo $severity_color; ?>"><?php echo ucfirst($violation['severity']); ?></span>
									<span class="text-muted small"><?php echo h($violation['category']); ?></span>
								</div>
							</div>
							<div class="text-end">
								<div class="h4 mb-0 fw-bold text-primary"><?php echo $violation['count']; ?></div>
								<small class="text-muted">occurrences</small>
							</div>
						</div>
					<?php 
							$rank++;
						endforeach; 
					else: 
					?>
						<div class="text-center py-5 text-muted">
							<i class="bi bi-inbox fs-1 d-block mb-2"></i>
							<p>No violations recorded yet</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Incident Trends Chart -->
	<div class="row g-4 mb-4">
		<div class="col-12">
			<div class="card border-0 shadow-sm">
				<div class="card-header bg-white border-0">
					<div class="d-flex justify-content-between align-items-center">
						<h5 class="mb-0 fw-bold">
							<i class="bi bi-bar-chart text-primary"></i> Incident Trends (Last 7 Days)
						</h5>
						<div class="text-muted small">
							<i class="bi bi-info-circle"></i> Daily violation count
						</div>
					</div>
				</div>
				<div class="card-body">
					<canvas id="incidentTrendChart" height="80"></canvas>
				</div>
			</div>
		</div>
	</div>

	<!-- Additional Statistics Cards -->
	<div class="row g-3 mb-4">
		<div class="col-md-3">
			<div class="card stat-card green h-100 border-0 shadow-sm">
				<div class="card-body">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<div class="fw-semibold mb-1"><i class="bi bi-people"></i> Total Students</div>
							<div class="display-5 fw-bold"><?php echo $students_count; ?></div>
						</div>
						<div class="fs-1 opacity-50">
							<i class="bi bi-people-fill"></i>
						</div>
					</div>
					<a href="students.php" class="btn btn-light btn-sm mt-3 w-100">
						<i class="bi bi-arrow-right"></i> Manage
					</a>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card stat-card rust h-100 border-0 shadow-sm">
				<div class="card-body">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle"></i> Total Violations</div>
							<div class="display-5 fw-bold"><?php echo $violations_count; ?></div>
						</div>
						<div class="fs-1 opacity-50">
							<i class="bi bi-exclamation-triangle-fill"></i>
						</div>
					</div>
					<a href="violations.php" class="btn btn-light btn-sm mt-3 w-100">
						<i class="bi bi-arrow-right"></i> Manage
					</a>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card stat-card tan h-100 border-0 shadow-sm">
				<div class="card-body">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<div class="fw-semibold mb-1"><i class="bi bi-file-earmark-text"></i> Total Records</div>
							<div class="display-5 fw-bold"><?php echo $records_count; ?></div>
						</div>
						<div class="fs-1 opacity-50">
							<i class="bi bi-file-earmark-text-fill"></i>
						</div>
					</div>
					<a href="records.php" class="btn btn-light btn-sm mt-3 w-100">
						<i class="bi bi-arrow-right"></i> Manage
					</a>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card stat-card rust h-100 border-0 shadow-sm">
				<div class="card-body">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<div class="fw-semibold mb-1"><i class="bi bi-clock-history"></i> Pending Actions</div>
							<div class="display-5 fw-bold"><?php echo $pending_dispositions; ?></div>
						</div>
						<div class="fs-1 opacity-50">
							<i class="bi bi-clock-history"></i>
						</div>
					</div>
					<a href="records.php" class="link-button d-block text-center mt-3 w-100">
						<i class="bi bi-arrow-right"></i> View
					</a>
				</div>
			</div>
		</div>
	</div>

	<!-- KPI Cards Row 2 - Monthly Trends -->
	<div class="row g-3 mb-4">
		<div class="col-md-6">
			<div class="card h-100">
				<div class="card-header bg-info text-white">
					<h5 class="mb-0">Monthly Violation Trend</h5>
				</div>
				<div class="card-body">
					<div class="row text-center">
						<div class="col-6">
							<h4 class="text-primary"><?php echo $current_month_records; ?></h4>
							<small class="text-muted">This Month</small>
						</div>
						<div class="col-6">
							<h4 class="text-secondary"><?php echo $last_month_records; ?></h4>
							<small class="text-muted">Last Month</small>
						</div>
					</div>
					<div class="mt-3 text-center">
						<span class="badge bg-<?php echo $monthly_trend >= 0 ? 'danger' : 'success'; ?>">
							<?php echo $monthly_trend >= 0 ? '+' : ''; ?><?php echo $monthly_trend; ?>%
						</span>
						<small class="text-muted">vs last month</small>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card h-100">
				<div class="card-header bg-secondary text-white">
					<h5 class="mb-0">Violations by Severity</h5>
				</div>
				<div class="card-body">
					<?php while ($severity = $severity_stats->fetch_assoc()): ?>
						<div class="d-flex justify-content-between align-items-center mb-2">
							<span class="badge bg-<?php echo $severity['severity']==='high'?'danger':($severity['severity']==='medium'?'warning':'secondary'); ?>">
								<?php echo ucfirst($severity['severity']); ?>
							</span>
							<strong><?php echo $severity['count']; ?></strong>
						</div>
					<?php endwhile; ?>
				</div>
			</div>
		</div>
	</div>

	<!-- KPI Cards Row 3 - Top Performers -->
	<div class="row g-3 mb-4">
		<div class="col-md-6">
			<div class="card h-100">
				<div class="card-header bg-dark text-white">
					<h5 class="mb-0">Top Violations</h5>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-sm">
							<thead>
								<tr>
									<th>Violation</th>
									<th>Category</th>
									<th>Count</th>
								</tr>
							</thead>
							<tbody>
								<?php while ($violation = $top_violations->fetch_assoc()): ?>
									<tr>
										<td>
											<?php echo h($violation['title']); ?>
											<span class="badge bg-<?php echo $violation['severity']==='high'?'danger':($violation['severity']==='medium'?'warning':'secondary'); ?> float-end">
												<?php echo ucfirst($violation['severity']); ?>
											</span>
										</td>
										<td><?php echo h($violation['category']); ?></td>
										<td><strong><?php echo $violation['count']; ?></strong></td>
									</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card h-100">
				<div class="card-header bg-dark text-white">
					<h5 class="mb-0">Students with Most Violations</h5>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-sm">
							<thead>
								<tr>
									<th>Student No</th>
									<th>Name</th>
									<th>Violations</th>
								</tr>
							</thead>
							<tbody>
								<?php while ($student = $top_students->fetch_assoc()): ?>
									<tr>
										<td><?php echo h($student['student_no']); ?></td>
										<td><?php echo h($student['last_name'] . ', ' . $student['first_name']); ?></td>
										<td><span class="badge bg-danger"><?php echo $student['violation_count']; ?></span></td>
									</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Recent Records Section -->
	<div class="card mt-4">
		<div class="card-header">Recent Violation Records</div>
		<div class="table-responsive">
			<table class="table table-sm mb-0">
				<thead>
					<tr>
						<th>Date</th>
						<th>Student</th>
						<th>Violation</th>
						<th>Severity</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($row = $recent->fetch_assoc()): ?>
						<tr>
							<td><?php echo h($row['occurred_at']); ?></td>
							<td><?php echo h($row['student_no'] . ' - ' . $row['last_name'] . ', ' . $row['first_name']); ?></td>
							<td><?php echo h($row['title']); ?></td>
							<td><span class="badge text-bg-<?php echo $row['severity']==='high'?'danger':($row['severity']==='medium'?'warning':'secondary'); ?>"><?php echo h($row['severity']); ?></span></td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- Chart.js for trend visualization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('incidentTrendChart');
    if (!ctx) return;
    
    const trendData = <?php echo json_encode($trend_data); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: trendData.map(d => d.day),
            datasets: [{
                label: 'Violations',
                data: trendData.map(d => d.count),
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        title: function(context) {
                            const index = context[0].dataIndex;
                            return trendData[index].date;
                        },
                        label: function(context) {
                            return 'Violations: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>

