<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = $_SESSION['user'];
if ($user['role'] !== 'student') {
	header('Location: dashboard.php');
	exit;
}
$db = get_db_connection();

// Find the student linked to this user
$stmt = $db->prepare('SELECT * FROM students WHERE user_id = ?');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
	$noLink = true;
}

// Load records and statistics for this student if linked
if (empty($noLink)) {
	// Get all violation records
	$rec = $db->prepare('SELECT vr.id, vr.occurred_at, vr.notes, vr.disposition, v.title, v.category, v.severity, v.description
		FROM violation_records vr
		JOIN violations v ON v.id = vr.violation_id
		WHERE vr.student_id = ?
		ORDER BY vr.occurred_at DESC');
	$rec->bind_param('i', $student['id']);
	$rec->execute();
	$records = $rec->get_result();
	
	// Get statistics
	$total_violations = $db->prepare('SELECT COUNT(*) as total FROM violation_records WHERE student_id = ?');
	$total_violations->bind_param('i', $student['id']);
	$total_violations->execute();
	$total_count = $total_violations->get_result()->fetch_assoc()['total'] ?? 0;
	
	// Get violations this month
	$current_month = date('Y-m');
	$month_violations = $db->prepare("SELECT COUNT(*) as total FROM violation_records WHERE student_id = ? AND DATE_FORMAT(occurred_at, '%Y-%m') = ?");
	$month_violations->bind_param('is', $student['id'], $current_month);
	$month_violations->execute();
	$month_count = $month_violations->get_result()->fetch_assoc()['total'] ?? 0;
	
	// Get violations by severity
	$severity_stats = $db->prepare('SELECT v.severity, COUNT(*) as count 
		FROM violation_records vr
		JOIN violations v ON v.id = vr.violation_id
		WHERE vr.student_id = ?
		GROUP BY v.severity');
	$severity_stats->bind_param('i', $student['id']);
	$severity_stats->execute();
	$severity_data = $severity_stats->get_result();
	
	// Get pending dispositions
	$pending = $db->prepare("SELECT COUNT(*) as total FROM violation_records WHERE student_id = ? AND (disposition IS NULL OR disposition = '')");
	$pending->bind_param('i', $student['id']);
	$pending->execute();
	$pending_count = $pending->get_result()->fetch_assoc()['total'] ?? 0;
	
	// Get recent violations (last 7 days)
	$week_start = date('Y-m-d', strtotime('-7 days'));
	$recent_violations = $db->prepare("SELECT COUNT(*) as total FROM violation_records WHERE student_id = ? AND occurred_at >= ?");
	$recent_violations->bind_param('is', $student['id'], $week_start);
	$recent_violations->execute();
	$recent_count = $recent_violations->get_result()->fetch_assoc()['total'] ?? 0;
	
	// Get violation trends (last 6 months)
	$trend_data = [];
	for ($i = 5; $i >= 0; $i--) {
		$month = date('Y-m', strtotime("-$i months"));
		$month_name = date('M Y', strtotime("-$i months"));
		$trend_query = $db->prepare("SELECT COUNT(*) as total FROM violation_records WHERE student_id = ? AND DATE_FORMAT(occurred_at, '%Y-%m') = ?");
		$trend_query->bind_param('is', $student['id'], $month);
		$trend_query->execute();
		$trend_count = $trend_query->get_result()->fetch_assoc()['total'] ?? 0;
		$trend_data[] = ['month' => $month_name, 'count' => $trend_count];
	}
}
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/nav.php'; ?>
<div class="container">
	<?php if (!empty($noLink)): ?>
		<div class="alert alert-warning">
			<i class="bi bi-exclamation-triangle"></i> Your account is not yet linked to a student record. Please contact the school office.
		</div>
	<?php else: ?>
		<!-- Header Section -->
		<div class="d-flex justify-content-between align-items-center mb-4">
			<div>
				<h2 class="fw-bold mb-1"><i class="bi bi-person-circle text-primary"></i> My Violation Dashboard</h2>
				<p class="text-muted mb-0">Monitor and track your violation records</p>
			</div>
			<div class="text-end">
				<div class="text-muted small">
					<i class="bi bi-calendar3"></i> <?php echo date('F j, Y'); ?>
				</div>
			</div>
		</div>

		<!-- Student Info Card -->
		<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
			<div class="card-body text-white p-4">
				<div class="row align-items-center">
					<div class="col-md-8">
						<h4 class="fw-bold mb-2"><i class="bi bi-person-badge"></i> Student Information</h4>
						<div class="row">
							<div class="col-md-6 mb-2">
								<strong>Student No:</strong> <?php echo h($student['student_no']); ?>
							</div>
							<div class="col-md-6 mb-2">
								<strong>Name:</strong> <?php echo h($student['last_name'] . ', ' . $student['first_name']); ?>
							</div>
							<div class="col-md-6 mb-2">
								<strong>Class:</strong> <?php echo h($student['class'] ?: 'N/A'); ?>
							</div>
							<div class="col-md-6 mb-2">
								<strong>Section:</strong> <?php echo h($student['section'] ?: 'N/A'); ?>
							</div>
						</div>
					</div>
					<div class="col-md-4 text-end">
						<div style="font-size: 4rem; opacity: 0.3;">🎓</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Statistics Cards -->
		<div class="row g-3 mb-4">
			<div class="col-md-3">
				<div class="card border-0 shadow-sm text-bg-primary h-100">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<div class="fw-semibold mb-1"><i class="bi bi-clipboard-data"></i> Total Violations</div>
								<div class="display-5 fw-bold"><?php echo $total_count; ?></div>
							</div>
							<div class="fs-1 opacity-50">
								<i class="bi bi-file-earmark-text-fill"></i>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card border-0 shadow-sm text-bg-warning h-100">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<div class="fw-semibold mb-1"><i class="bi bi-calendar-month"></i> This Month</div>
								<div class="display-5 fw-bold"><?php echo $month_count; ?></div>
							</div>
							<div class="fs-1 opacity-50">
								<i class="bi bi-calendar-check-fill"></i>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card border-0 shadow-sm text-bg-info h-100">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<div class="fw-semibold mb-1"><i class="bi bi-calendar-week"></i> Last 7 Days</div>
								<div class="display-5 fw-bold"><?php echo $recent_count; ?></div>
							</div>
							<div class="fs-1 opacity-50">
								<i class="bi bi-clock-history"></i>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card border-0 shadow-sm text-bg-danger h-100">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<div class="fw-semibold mb-1"><i class="bi bi-exclamation-circle"></i> Pending</div>
								<div class="display-5 fw-bold"><?php echo $pending_count; ?></div>
							</div>
							<div class="fs-1 opacity-50">
								<i class="bi bi-hourglass-split"></i>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Severity Breakdown -->
		<div class="row g-3 mb-4">
			<div class="col-md-6">
				<div class="card border-0 shadow-sm h-100">
					<div class="card-header bg-primary text-white">
						<h5 class="mb-0"><i class="bi bi-pie-chart"></i> Violations by Severity</h5>
					</div>
					<div class="card-body">
						<?php 
						$severity_data->data_seek(0);
						if ($severity_data->num_rows > 0): 
							while ($sev = $severity_data->fetch_assoc()): 
								$severity_color = $sev['severity'] === 'high' ? 'danger' : ($sev['severity'] === 'medium' ? 'warning' : 'secondary');
								$percentage = $total_count > 0 ? round(($sev['count'] / $total_count) * 100, 1) : 0;
						?>
							<div class="mb-3">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<span class="badge bg-<?php echo $severity_color; ?> severity-<?php echo $sev['severity']; ?>">
										<?php echo ucfirst($sev['severity']); ?>
									</span>
									<strong><?php echo $sev['count']; ?> (<?php echo $percentage; ?>%)</strong>
								</div>
								<div class="progress" style="height: 20px;">
									<div class="progress-bar bg-<?php echo $severity_color; ?>" role="progressbar" 
										style="width: <?php echo $percentage; ?>%" 
										aria-valuenow="<?php echo $percentage; ?>" 
										aria-valuemin="0" 
										aria-valuemax="100">
										<?php echo $percentage; ?>%
									</div>
								</div>
							</div>
						<?php 
							endwhile;
						else: 
						?>
							<div class="text-center py-3 text-muted">
								<i class="bi bi-check-circle fs-1 d-block mb-2"></i>
								<p class="mb-0">No violations recorded</p>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card border-0 shadow-sm h-100">
					<div class="card-header bg-info text-white">
						<h5 class="mb-0"><i class="bi bi-bar-chart"></i> Violation Trends (Last 6 Months)</h5>
					</div>
					<div class="card-body">
						<canvas id="violationTrendChart" height="200"></canvas>
					</div>
				</div>
			</div>
		</div>

		<!-- Violation Records Table -->
		<div class="card border-0 shadow-sm">
			<div class="card-header bg-white border-0">
				<div class="d-flex justify-content-between align-items-center">
					<h5 class="mb-0 fw-bold"><i class="bi bi-list-ul"></i> My Violation Records</h5>
					<div class="text-muted small">
						Total: <strong><?php echo $total_count; ?></strong> records
					</div>
				</div>
			</div>
			<div class="card-body p-0">
				<?php if ($total_count > 0): ?>
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead class="table-light">
								<tr>
									<th>Date</th>
									<th>Violation</th>
									<th>Category</th>
									<th>Severity</th>
									<th>Notes</th>
									<th>Disposition</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php 
								$records->data_seek(0);
								while ($row = $records->fetch_assoc()): 
								?>
									<tr>
										<td>
											<strong><?php echo date('M j, Y', strtotime($row['occurred_at'])); ?></strong><br>
											<small class="text-muted"><?php echo date('g:i A', strtotime($row['occurred_at'])); ?></small>
										</td>
										<td>
											<strong><?php echo h($row['title']); ?></strong>
											<?php if ($row['description']): ?>
												<br><small class="text-muted"><?php echo h(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : ''); ?></small>
											<?php endif; ?>
										</td>
										<td>
											<span class="badge bg-secondary"><?php echo h($row['category']); ?></span>
										</td>
										<td>
											<span class="badge text-bg-<?php echo $row['severity']==='high'?'danger':($row['severity']==='medium'?'warning':'secondary'); ?> severity-<?php echo $row['severity']; ?>">
												<?php echo ucfirst(h($row['severity'])); ?>
											</span>
										</td>
										<td>
											<?php if ($row['notes']): ?>
												<button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#notesModal<?php echo $row['id']; ?>">
													<i class="bi bi-file-text"></i> View Notes
												</button>
											<?php else: ?>
												<span class="text-muted">-</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($row['disposition']): ?>
												<span class="badge bg-success"><?php echo h($row['disposition']); ?></span>
											<?php else: ?>
												<span class="badge bg-warning">Pending</span>
											<?php endif; ?>
										</td>
										<td>
											<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $row['id']; ?>">
												<i class="bi bi-eye"></i> Details
											</button>
										</td>
									</tr>

									<!-- Notes Modal -->
									<?php if ($row['notes']): ?>
									<div class="modal fade" id="notesModal<?php echo $row['id']; ?>" tabindex="-1">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title">Violation Notes</h5>
													<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
												</div>
												<div class="modal-body">
													<p><strong>Violation:</strong> <?php echo h($row['title']); ?></p>
													<p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($row['occurred_at'])); ?></p>
													<hr>
													<p><strong>Notes:</strong></p>
													<p><?php echo nl2br(h($row['notes'])); ?></p>
												</div>
												<div class="modal-footer">
													<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
												</div>
											</div>
										</div>
									</div>
									<?php endif; ?>

									<!-- Detail Modal -->
									<div class="modal fade" id="detailModal<?php echo $row['id']; ?>" tabindex="-1">
										<div class="modal-dialog modal-lg">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title">Violation Details</h5>
													<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
												</div>
												<div class="modal-body">
													<div class="row mb-3">
														<div class="col-md-6">
															<strong>Date:</strong><br>
															<?php echo date('F j, Y g:i A', strtotime($row['occurred_at'])); ?>
														</div>
														<div class="col-md-6">
															<strong>Severity:</strong><br>
															<span class="badge text-bg-<?php echo $row['severity']==='high'?'danger':($row['severity']==='medium'?'warning':'secondary'); ?> severity-<?php echo $row['severity']; ?>">
																<?php echo ucfirst(h($row['severity'])); ?>
															</span>
														</div>
													</div>
													<div class="row mb-3">
														<div class="col-md-6">
															<strong>Violation:</strong><br>
															<?php echo h($row['title']); ?>
														</div>
														<div class="col-md-6">
															<strong>Category:</strong><br>
															<span class="badge bg-secondary"><?php echo h($row['category']); ?></span>
														</div>
													</div>
													<?php if ($row['description']): ?>
													<div class="mb-3">
														<strong>Description:</strong><br>
														<?php echo nl2br(h($row['description'])); ?>
													</div>
													<?php endif; ?>
													<?php if ($row['notes']): ?>
													<div class="mb-3">
														<strong>Notes:</strong><br>
														<?php echo nl2br(h($row['notes'])); ?>
													</div>
													<?php endif; ?>
													<div class="mb-3">
														<strong>Disposition:</strong><br>
														<?php if ($row['disposition']): ?>
															<span class="badge bg-success"><?php echo h($row['disposition']); ?></span>
														<?php else: ?>
															<span class="badge bg-warning">Pending</span>
														<?php endif; ?>
													</div>
												</div>
												<div class="modal-footer">
													<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
												</div>
											</div>
										</div>
									</div>
								<?php endwhile; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<div class="text-center py-5">
						<i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
						<h5 class="mt-3">No Violations Recorded</h5>
						<p class="text-muted">You have a clean record! Keep up the good work.</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Chart.js for trend visualization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('violationTrendChart');
    if (!ctx) return;
    
    const trendData = <?php echo json_encode($trend_data); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.map(d => d.month),
            datasets: [{
                label: 'Violations',
                data: trendData.map(d => d.count),
                borderColor: 'rgb(102, 126, 234)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: 'rgb(102, 126, 234)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
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

