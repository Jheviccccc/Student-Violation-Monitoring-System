<?php
require_once __DIR__ . '/auth.php';
require_role('admin');
$db = get_db_connection();

// Get time period from request
$period = $_GET['period'] ?? 'month';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

// Build date range based on period
switch ($period) {
    case 'year':
        $from = "$year-01-01";
        $to = "$year-12-31";
        break;
    case 'month':
        $from = "$year-$month-01";
        $to = date('Y-m-t', strtotime("$year-$month-01"));
        break;
    case 'week':
        $from = date('Y-m-d', strtotime('monday this week'));
        $to = date('Y-m-d', strtotime('sunday this week'));
        break;
    default:
        $from = date('Y-m-01');
        $to = date('Y-m-d');
}

// Get comprehensive statistics
$stats = [];

// Total counts
$stmt = $db->prepare('SELECT COUNT(*) FROM students');
$stmt->execute();
$stats['total_students'] = (int)$stmt->get_result()->fetch_row()[0];

$stmt = $db->prepare('SELECT COUNT(*) FROM violations');
$stmt->execute();
$stats['total_violations'] = (int)$stmt->get_result()->fetch_row()[0];

$stmt = $db->prepare('SELECT COUNT(*) FROM violation_records WHERE occurred_at BETWEEN ? AND ?');
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$stats['total_records'] = (int)$stmt->get_result()->fetch_row()[0] ?? 0;

// Violations by category
$stmt = $db->prepare('
    SELECT v.category, COUNT(vr.id) as count 
    FROM violations v 
    LEFT JOIN violation_records vr ON v.id = vr.violation_id AND vr.occurred_at BETWEEN ? AND ?
    GROUP BY v.category 
    ORDER BY count DESC');
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$stats['by_category'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Violations by severity
$stmt = $db->prepare('
    SELECT v.severity, COUNT(vr.id) as count 
    FROM violations v 
    LEFT JOIN violation_records vr ON v.id = vr.violation_id AND vr.occurred_at BETWEEN ? AND ?
    GROUP BY v.severity 
    ORDER BY count DESC');
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$stats['by_severity'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Top students with violations
$stmt = $db->prepare('
    SELECT s.student_no, s.first_name, s.last_name, s.class, COUNT(vr.id) as violation_count 
    FROM students s 
    LEFT JOIN violation_records vr ON s.id = vr.student_id AND vr.occurred_at BETWEEN ? AND ?
    GROUP BY s.id 
    HAVING violation_count > 0 
    ORDER BY violation_count DESC 
    LIMIT 10');
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$stats['top_students'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Monthly trend for the selected period
$stmt = $db->prepare('
    SELECT DATE_FORMAT(occurred_at, "%Y-%m") as month, COUNT(*) as count 
    FROM violation_records 
    WHERE occurred_at BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(occurred_at, "%Y-%m")
    ORDER BY month');
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$stats['monthly_trend'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Violations by day of week
$stmt = $db->prepare('
    SELECT DAYNAME(occurred_at) as day_name, COUNT(*) as count 
    FROM violation_records 
    WHERE occurred_at BETWEEN ? AND ?
    GROUP BY DAYOFWEEK(occurred_at), DAYNAME(occurred_at)
    ORDER BY DAYOFWEEK(occurred_at)');
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$stats['by_day_of_week'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Pending dispositions
$stmt = $db->prepare("SELECT COUNT(*) FROM violation_records WHERE (disposition IS NULL OR disposition = '') AND occurred_at BETWEEN ? AND ?");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$stats['pending_dispositions'] = (int)$stmt->get_result()->fetch_row()[0] ?? 0;

// Average violations per student
$stats['avg_violations_per_student'] = $stats['total_students'] > 0 ? round($stats['total_records'] / $stats['total_students'], 2) : 0;

// Get available years for dropdown
$years_result = $db->query('SELECT DISTINCT YEAR(occurred_at) as year FROM violation_records ORDER BY year DESC');
$years = $years_result ? $years_result->fetch_all(MYSQLI_ASSOC) : [];
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/nav.php'; ?>

<div class="container">
    <h4 class="mb-4">Statistics & Analytics</h4>
    
    <!-- Period Selection -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Time Period Selection</h5>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Period</label>
                    <select class="form-select" name="period" onchange="this.form.submit()">
                        <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <select class="form-select" name="year" onchange="this.form.submit()">
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y['year']; ?>" <?php echo $year == $y['year'] ? 'selected' : ''; ?>>
                                <?php echo $y['year']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($period === 'month'): ?>
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select class="form-select" name="month" onchange="this.form.submit()">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                    <?php echo $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-bg-primary h-100">
                <div class="card-body text-center">
                    <h2><?php echo $stats['total_records']; ?></h2>
                    <p class="mb-0">Total Violations</p>
                    <small><?php echo date('M j, Y', strtotime($from)); ?> - <?php echo date('M j, Y', strtotime($to)); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-info h-100">
                <div class="card-body text-center">
                    <h2><?php echo $stats['total_students']; ?></h2>
                    <p class="mb-0">Total Students</p>
                    <small>In System</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-warning h-100">
                <div class="card-body text-center">
                    <h2><?php echo $stats['avg_violations_per_student']; ?></h2>
                    <p class="mb-0">Avg Violations/Student</p>
                    <small>Current Period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-danger h-100">
                <div class="card-body text-center">
                    <h2><?php echo $stats['pending_dispositions']; ?></h2>
                    <p class="mb-0">Pending Actions</p>
                    <small>Need Attention</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Statistics -->
    <div class="row g-4">
        <!-- Violations by Category -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Violations by Category</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_by_category = array_sum(array_column($stats['by_category'], 'count'));
                                foreach ($stats['by_category'] as $cat): 
                                    $percentage = $total_by_category > 0 ? round(($cat['count'] / $total_by_category) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td><?php echo h($cat['category']); ?></td>
                                        <td><strong><?php echo $cat['count']; ?></strong></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $percentage; ?>%"
                                                     aria-valuenow="<?php echo $percentage; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $percentage; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Violations by Severity -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Violations by Severity</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Severity</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_by_severity = array_sum(array_column($stats['by_severity'], 'count'));
                                foreach ($stats['by_severity'] as $sev): 
                                    $percentage = $total_by_severity > 0 ? round(($sev['count'] / $total_by_severity) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php echo $sev['severity']==='high'?'danger':($sev['severity']==='medium'?'warning':'secondary'); ?>">
                                                <?php echo ucfirst($sev['severity']); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo $sev['count']; ?></strong></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-<?php echo $sev['severity']==='high'?'danger':($sev['severity']==='medium'?'warning':'secondary'); ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $percentage; ?>%"
                                                     aria-valuenow="<?php echo $percentage; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $percentage; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Students with Violations -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Top Students with Violations</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Student No</th>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Violations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['top_students'] as $student): ?>
                                    <tr>
                                        <td><?php echo h($student['student_no']); ?></td>
                                        <td><?php echo h($student['last_name'] . ', ' . $student['first_name']); ?></td>
                                        <td><?php echo h($student['class']); ?></td>
                                        <td><span class="badge bg-danger"><?php echo $student['violation_count']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Violations by Day of Week -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Violations by Day of Week</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_by_day = array_sum(array_column($stats['by_day_of_week'], 'count'));
                                foreach ($stats['by_day_of_week'] as $day): 
                                    $percentage = $total_by_day > 0 ? round(($day['count'] / $total_by_day) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td><?php echo h($day['day_name']); ?></td>
                                        <td><strong><?php echo $day['count']; ?></strong></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-info" role="progressbar" 
                                                     style="width: <?php echo $percentage; ?>%"
                                                     aria-valuenow="<?php echo $percentage; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $percentage; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend Chart -->
    <?php if (count($stats['monthly_trend']) > 1): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Monthly Trend</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Violations</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $previous = null;
                            foreach ($stats['monthly_trend'] as $trend): 
                                $current = $trend['count'];
                                $trend_text = '';
                                $trend_class = '';
                                
                                if ($previous !== null) {
                                    if ($current > $previous) {
                                        $trend_text = '↗ Increasing';
                                        $trend_class = 'text-danger';
                                    } elseif ($current < $previous) {
                                        $trend_text = '↘ Decreasing';
                                        $trend_class = 'text-success';
                                    } else {
                                        $trend_text = '→ Stable';
                                        $trend_class = 'text-muted';
                                    }
                                }
                                $previous = $current;
                            ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($trend['month'] . '-01')); ?></td>
                                    <td><strong><?php echo $trend['count']; ?></strong></td>
                                    <td class="<?php echo $trend_class; ?>"><?php echo $trend_text; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>