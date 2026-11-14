
<?php
require_once __DIR__ . '/../includes/dashboard_auth.php';
require_once __DIR__ . '/../includes/q5_db.php';

// Verify login and dashboard permissions
$dashboardAccess = q5_require_dashboard_access();

$isSuperAdmin = ($dashboardAccess === 'full');
$isAdmin = ($dashboardAccess === 'limited');

$conn = q5_get_db_connection();

// Escape output
function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ==========================================
// SUPER ADMIN: FULL SECURITY ANALYTICS
// ==========================================

if ($isSuperAdmin) {

    // Total audit events
    $totalActivities = (int) $conn->query(
        "SELECT COUNT(*) AS total
         FROM security_audit_logs"
    )->fetch_assoc()['total'];

    // Successful login events
    $successfulLogins = (int) $conn->query(
        "SELECT COUNT(*) AS total
         FROM security_audit_logs
         WHERE action = 'USER_LOGIN'
         AND status = 'SUCCESS'"
    )->fetch_assoc()['total'];

    // Failed login events
    $failedLogins = (int) $conn->query(
        "SELECT COUNT(*) AS total
         FROM security_audit_logs
         WHERE action = 'FAILED_LOGIN'
         AND status = 'FAILED'"
    )->fetch_assoc()['total'];

    // Users with recorded activity
    $activeUsers = (int) $conn->query(
        "SELECT COUNT(DISTINCT actor_user_id) AS total
         FROM security_audit_logs
         WHERE actor_user_id IS NOT NULL"
    )->fetch_assoc()['total'];

    // Currently blocked account/IP combinations
    $blockedAccounts = (int) $conn->query(
        "SELECT COUNT(*) AS total
         FROM login_security_blocks
         WHERE blocked_until > NOW()"
    )->fetch_assoc()['total'];

    // Recent audit events - full details
    $recentLogs = $conn->query(
        "SELECT actor_user_id, actor_role, action,
                category, status, ip_address, created_at
         FROM security_audit_logs
         ORDER BY created_at DESC
         LIMIT 10"
    );

    // Login chart data
    $loginChartData = [
        'Successful' => $successfulLogins,
        'Failed' => $failedLogins
    ];
}


// ==========================================
// ADMIN: LIMITED OPERATIONAL DASHBOARD
// ==========================================

if ($isAdmin) {

    // Only count non-authentication activities
    $totalActivities = (int) $conn->query(
        "SELECT COUNT(*) AS total
         FROM security_audit_logs
         WHERE category <> 'Authentication'"
    )->fetch_assoc()['total'];

    // Count users with recorded operational activity
    $activeUsers = (int) $conn->query(
        "SELECT COUNT(DISTINCT actor_user_id) AS total
         FROM security_audit_logs
         WHERE actor_user_id IS NOT NULL
         AND category <> 'Authentication'"
    )->fetch_assoc()['total'];

    // Recent operational events only
    // Do not retrieve user IDs or IP addresses
    $recentLogs = $conn->query(
        "SELECT actor_role, action, category,
                status, created_at
         FROM security_audit_logs
         WHERE category <> 'Authentication'
         ORDER BY created_at DESC
         LIMIT 10"
    );
}


// ==========================================
// DAILY ACTIVITY TREND
// ==========================================

// Super Admin sees all activity.
// Admin sees operational activity only.

if ($isSuperAdmin) {

    $trendResult = $conn->query(
        "SELECT DATE(created_at) AS log_date,
                COUNT(*) AS total
         FROM security_audit_logs
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(created_at)
         ORDER BY log_date ASC"
    );

} else {

    $trendResult = $conn->query(
        "SELECT DATE(created_at) AS log_date,
                COUNT(*) AS total
         FROM security_audit_logs
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         AND category <> 'Authentication'
         GROUP BY DATE(created_at)
         ORDER BY log_date ASC"
    );
}

$dailyActivity = [];

while ($row = $trendResult->fetch_assoc()) {
    $dailyActivity[$row['log_date']] = (int) $row['total'];
}

$trendLabels = [];
$trendValues = [];

for ($i = 6; $i >= 0; $i--) {

    $date = date('Y-m-d', strtotime("-$i days"));

    $trendLabels[] = date('d M', strtotime($date));
    $trendValues[] = $dailyActivity[$date] ?? 0;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Q5 Security Analytics Dashboard</title>

    <style>
        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #222;
        }

        h1 {
            margin-bottom: 5px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 15px;
        }

        .role-label {
            display: inline-block;
            padding: 7px 12px;
            margin-bottom: 25px;
            border-radius: 5px;
            background: #e9ecef;
            color: #333;
            font-size: 13px;
            font-weight: bold;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }

        .card h3 {
            color: #666;
            font-size: 14px;
        }

        .number {
            font-size: 30px;
            font-weight: bold;
        }

        .section {
            background: white;
            padding: 22px;
            border-radius: 10px;
            overflow-x: auto;
            margin-top: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        th {
            background: #f8f9fa;
        }

        .success {
            color: #16803c;
            font-weight: bold;
        }

        .failed {
            color: #d62828;
            font-weight: bold;
        }

        .charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            min-width: 0;
        }

        .chart-card h2 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 280px;
        }
    </style>
</head>

<body>

    <h1>Security Analytics Dashboard</h1>

    <p class="subtitle">
        Pharmacy Management System — Q5 Logging & Analytics
    </p>

    <?php if ($isSuperAdmin): ?>
        <div class="role-label">
            Super Admin — Full Security Access
        </div>
    <?php else: ?>
        <div class="role-label">
            Admin — Limited Operational Access
        </div>
    <?php endif; ?>


    <!-- ==================================
         SUMMARY CARDS
    =================================== -->

    <div class="cards">

        <div class="card">
            <h3>
                <?= $isSuperAdmin ? 'Total Activities' : 'Operational Activities' ?>
            </h3>

            <div class="number">
                <?= $totalActivities ?>
            </div>
        </div>

        <?php if ($isSuperAdmin): ?>

            <div class="card">
                <h3>Successful Logins</h3>
                <div class="number">
                    <?= $successfulLogins ?>
                </div>
            </div>

            <div class="card">
                <h3>Failed Logins</h3>
                <div class="number">
                    <?= $failedLogins ?>
                </div>
            </div>

        <?php endif; ?>

        <div class="card">
            <h3>Users with Recorded Activity</h3>
            <div class="number">
                <?= $activeUsers ?>
            </div>
        </div>

        <?php if ($isSuperAdmin): ?>

            <div class="card">
                <h3>Currently Blocked Accounts</h3>
                <div class="number">
                    <?= $blockedAccounts ?>
                </div>
            </div>

        <?php endif; ?>

    </div>


    <!-- ==================================
         ANALYTICS CHARTS
    =================================== -->

    <div class="charts">

        <?php if ($isSuperAdmin): ?>

            <div class="chart-card">
                <h2>Login Activity</h2>

                <div class="chart-container">
                    <canvas id="loginChart"></canvas>
                </div>
            </div>

        <?php endif; ?>

        <div class="chart-card">
            <h2>
                <?= $isSuperAdmin
                    ? 'Daily Activity Trends'
                    : 'Daily Operational Activity Trends' ?>
            </h2>

            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

    </div>


    <!-- ==================================
         RECENT AUDIT LOGS
    =================================== -->

    <div class="section">

        <h2>
            <?= $isSuperAdmin
                ? 'Recent Audit Logs'
                : 'Recent Operational Activities' ?>
        </h2>

        <table>

            <thead>
                <tr>

                    <?php if ($isSuperAdmin): ?>
                        <th>User ID</th>
                    <?php endif; ?>

                    <th>Role</th>
                    <th>Action</th>
                    <th>Category</th>
                    <th>Status</th>

                    <?php if ($isSuperAdmin): ?>
                        <th>IP Address</th>
                    <?php endif; ?>

                    <th>Date & Time</th>

                </tr>
            </thead>

            <tbody>

                <?php if ($recentLogs->num_rows > 0): ?>

                    <?php while ($log = $recentLogs->fetch_assoc()): ?>

                        <tr>

                            <?php if ($isSuperAdmin): ?>
                                <td>
                                    <?= e($log['actor_user_id'] ?? 'N/A') ?>
                                </td>
                            <?php endif; ?>

                            <td><?= e($log['actor_role']) ?></td>

                            <td><?= e($log['action']) ?></td>

                            <td><?= e($log['category']) ?></td>

                            <td class="<?= $log['status'] === 'SUCCESS' ? 'success' : ($log['status'] === 'FAILED' ? 'failed' : '') ?>">
                                <?= e($log['status']) ?>
                            </td>

                            <?php if ($isSuperAdmin): ?>
                                <td>
                                    <?= e($log['ip_address'] ?? 'N/A') ?>
                                </td>
                            <?php endif; ?>

                            <td><?= e($log['created_at']) ?></td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="<?= $isSuperAdmin ? 7 : 5 ?>">
                            No activity records found.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- ==================================
         CHART.JS
    =================================== -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>

        const trendLabels = <?= json_encode(
            $trendLabels,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;

        const trendValues = <?= json_encode(
            $trendValues,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ) ?>;


        <?php if ($isSuperAdmin): ?>

            const loginValues = <?= json_encode(
                array_values($loginChartData),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
            ) ?>;

            // Super Admin login activity chart
            new Chart(document.getElementById('loginChart'), {

                type: 'doughnut',

                data: {
                    labels: ['Successful', 'Failed'],

                    datasets: [{
                        data: loginValues,
                        backgroundColor: ['#28a745', '#dc3545']
                    }]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }

            });

        <?php endif; ?>


        // Daily activity trend - available to both roles
        new Chart(document.getElementById('trendChart'), {

            type: 'line',

            data: {

                labels: trendLabels,

                datasets: [{

                    label: 'Recorded activities',

                    data: trendValues,

                    borderColor: '#dc3545',

                    backgroundColor: 'rgba(220, 53, 69, 0.12)',

                    fill: true,

                    tension: 0.3

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {
                            precision: 0
                        }

                    }

                }

            }

        });

    </script>

</body>
</html>
