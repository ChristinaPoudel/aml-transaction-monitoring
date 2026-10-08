<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/config.php';

$pdo = db();

$riskFilter = $_GET['risk'] ?? '';
$customerSearch = trim($_GET['search'] ?? '');


/*
 * Search customers by risk
 */
$riskQuery = "
    SELECT
        r.customer_id,
        c.full_name,
        r.velocity_score,
        r.mcc_score,
        r.total_score,
        r.risk_band
    FROM risk_scores r
    JOIN customers c
        ON c.customer_id = r.customer_id
    WHERE r.score_date = CURRENT_DATE
";

$params = [];

if (
    $riskFilter !== ''
    && in_array($riskFilter, ['LOW', 'MEDIUM', 'HIGH'], true)
) {
    $riskQuery .= " AND r.risk_band = :risk";
    $params['risk'] = $riskFilter;
}

if ($customerSearch !== '') {

    $riskQuery .= "
        AND (
            c.full_name ILIKE :search
            OR CAST(c.customer_id AS TEXT) ILIKE :search
        )
    ";

    $params['search'] = '%' . $customerSearch . '%';
}

$riskQuery .= " ORDER BY r.total_score DESC";

$riskStmt = $pdo->prepare($riskQuery);
$riskStmt->execute($params);

$riskCustomers = $riskStmt->fetchAll();


/*
 * Run transaction detection
 */
if (isset($_POST['run_detection'])) {

    ob_start();

    require __DIR__ . '/detect.php';

    $newAlerts = ob_get_clean();

    $detectionMessage =
        'Detection completed. '
        . (int)$newAlerts
        . ' new alert(s) created.';

} else {

    $detectionMessage = '';
}


/*
 * Risk summary
 */
$riskSummary = $pdo->query(
    "SELECT
        risk_band,
        COUNT(*) AS customer_count
     FROM risk_scores
     WHERE score_date = CURRENT_DATE
     GROUP BY risk_band
     ORDER BY risk_band"
)->fetchAll();


/*
 * Highest risk customers
 */
$topRiskCustomers = $pdo->query(
    "SELECT
        customer_id,
        velocity_score,
        mcc_score,
        total_score,
        risk_band
     FROM risk_scores
     WHERE score_date = CURRENT_DATE
     ORDER BY total_score DESC
     LIMIT 5"
)->fetchAll();


/*
 * =========================
 * CHART DATA
 * =========================
 */


/*
 * Customer risk distribution
 */
$riskChart = [
    'HIGH' => 0,
    'MEDIUM' => 0,
    'LOW' => 0
];

foreach ($riskSummary as $row) {

    $riskBand = $row['risk_band'];

    if (isset($riskChart[$riskBand])) {

        $riskChart[$riskBand] = (int)$row['customer_count'];

    }
}


/*
 * Alert status distribution
 */
$alertStatusChart = [
    'OPEN' => 0,
    'UNDER_REVIEW' => 0,
    'ESCALATED' => 0,
    'CLOSED' => 0
];

$alertStatusRows = $pdo->query(
    "SELECT
        status,
        COUNT(*) AS total
     FROM alerts
     GROUP BY status
     ORDER BY status"
)->fetchAll();

foreach ($alertStatusRows as $row) {

    $status = $row['status'];

    if (isset($alertStatusChart[$status])) {

        $alertStatusChart[$status] = (int)$row['total'];

    }
}


/*
 * Merchant risk activity
 */
$merchantRows = $pdo->query(
    "SELECT
        mt.category_name,
        mt.risk_weight,
        COUNT(t.txn_id) AS transaction_count,
        COALESCE(SUM(t.amount), 0) AS transaction_amount
     FROM merchant_types mt
     LEFT JOIN transactions t
        ON t.merchant_type_id = mt.merchant_type_id
     GROUP BY
        mt.category_name,
        mt.risk_weight
     ORDER BY
        mt.risk_weight DESC"
)->fetchAll();


/*
 * Update alert status
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['status'], $_POST['alert_id'])
) {

    $newStatus = $_POST['status'];
    $alertId = (int)$_POST['alert_id'];

    if (
        $alertId > 0
        && in_array(
            $newStatus,
            ['OPEN', 'UNDER_REVIEW', 'ESCALATED', 'CLOSED'],
            true
        )
    ) {

        $update = $pdo->prepare(
            'UPDATE alerts
             SET status = :status
             WHERE alert_id = :id'
        );

        $update->execute([
            'status' => $newStatus,
            'id' => $alertId
        ]);
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}


/*
 * Get alerts
 */
$alerts = $pdo->query(
    "SELECT
        al.alert_id,
        al.created_at,
        al.rule_code,
        al.severity,
        al.status,
        al.details,
        c.full_name
     FROM alerts al
     JOIN customers c
        ON c.customer_id = al.customer_id
     ORDER BY al.alert_id DESC"
)->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>AML Compliance Dashboard</title>

    <!-- Bootstrap -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    >

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        body {
            background-color: #f4f6f9;
        }

        

        h1 {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        h2 {
            color: #1f2937;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .risk-high {
            color: white;
            background-color: #dc3545;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }

        .risk-medium {
            color: white;
            background-color: #f0ad4e;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }

        .risk-low {
            color: white;
            background-color: #28a745;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }

        .risk-card {
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.risk-card:hover {
    text-decoration: none;
    transform: translateY(-3px);
    box-shadow: 0 5px 12px rgba(0, 0, 0, 0.15);
}

        table {
            background-color: white;
        }

        th {
            font-weight: 600;
        }

        a {
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        button[name="run_detection"] {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        button[name="run_detection"]:hover {
            background-color: #0b5ed7;
        }

        input[type="text"],
        select {
            padding: 7px 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
        }

        button[type="submit"] {
            cursor: pointer;
        }

        /*
         * Risk summary
         */
        .risk-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .risk-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 6px solid;
            min-width: 180px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .risk-card h3 {
            margin: 0;
            font-size: 16px;
        }

        .risk-card .count {
            font-size: 30px;
            font-weight: bold;
            margin-top: 8px;
        }

        .risk-card.high {
            border-left-color: #dc3545;
        }

        .risk-card.medium {
            border-left-color: #f0ad4e;
        }

        .risk-card.low {
            border-left-color: #28a745;
        }

        /*
         * Analytics cards
         */
        .chart-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            height: 100%;
        }

        .chart-card h4 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .analytics-section {
            margin-top: 35px;
        }

        /*
         * Merchant table
         */
        .merchant-table {
            margin-top: 20px;
        }

    </style>

</head>

<body class="bg-light">

<div class="container py-4">


    <!-- Dashboard Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="mb-1">
                AML Compliance Dashboard
            </h1>

            <p class="text-muted mb-0">
                Transaction monitoring and customer risk assessment
            </p>

        </div>

        <div>

            <span class="badge bg-dark">
                AML Monitoring System
            </span>

        </div>

    </div>


    <!-- Run Detection -->

    <form method="post" action="dashboard.php">

        <button
            type="submit"
            name="run_detection"
            value="1"
        >
            Run Detection
        </button>

    </form>
    
<div class="mt-3">

    <a
        href="customer_manage.php"
        class="btn btn-success"
    >
        + Add Customer
    </a>

</div>

    <?php if ($detectionMessage !== ''): ?>

        <div class="alert alert-info mt-3">

            <?= htmlspecialchars($detectionMessage) ?>

        </div>

    <?php endif; ?>


    <!-- Risk Summary -->

    <h2>Risk Summary</h2>

   <div class="risk-summary">

    <?php foreach ($riskSummary as $row): ?>

        <?php

        $riskClass = 'low';

        if ($row['risk_band'] === 'HIGH') {

            $riskClass = 'high';

        } elseif ($row['risk_band'] === 'MEDIUM') {

            $riskClass = 'medium';

        }

        ?>

        <a
            href="dashboard.php?risk=<?= urlencode($row['risk_band']) ?>"
            class="risk-card <?= $riskClass ?>"
        >

            <h3>
                <?= htmlspecialchars($row['risk_band']) ?> Risk
            </h3>

            <div class="count">

                <?= htmlspecialchars($row['customer_count']) ?>

            </div>

            <div class="text-muted">

                Customers

            </div>

            <div class="mt-2 text-primary">

                View Customers →

            </div>

        </a>

    <?php endforeach; ?>

</div>


    <!-- =========================
         VISUAL ANALYTICS
    ========================== -->

    <div class="analytics-section">

        <h2>Visual Analytics</h2>

        <p class="text-muted">
            Visual summary of customer risk, alert status, and merchant risk activity.
        </p>


        <div class="row g-4">


            <!-- Customer Risk Distribution -->

            <div class="col-lg-4">

                <div class="chart-card">

                    <h4>
                        Customer Risk Distribution
                    </h4>

                    <div class="chart-container">

                        <canvas id="riskChart"></canvas>

                    </div>

                </div>

            </div>


            <!-- Alert Status -->

            <div class="col-lg-4">

                <div class="chart-card">

                    <h4>
                        Alert Status
                    </h4>

                    <div class="chart-container">

                        <canvas id="alertChart"></canvas>

                    </div>

                </div>

            </div>


            <!-- Merchant Risk Activity -->

            <div class="col-lg-4">

                <div class="chart-card">

                    <h4>
                        Merchant Risk Activity
                    </h4>

                    <div class="chart-container">

                        <canvas id="merchantChart"></canvas>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================
         MERCHANT RISK TABLE
    ========================== -->

    <div class="analytics-section">

        <h2>Merchant Risk Analysis</h2>

        <div class="table-responsive">

            <table class="table table-hover table-bordered align-middle">

                <thead class="table-dark">

                    <tr>

                        <th>Merchant Type</th>

                        <th>Risk Weight</th>

                        <th>Transactions</th>

                        <th>Transaction Amount</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($merchantRows as $merchant): ?>

                    <tr>

                        <td>

                            <strong>
<?= htmlspecialchars($merchant['category_name']) ?>                            </strong>

                        </td>

                        <td>

                            <?= htmlspecialchars($merchant['risk_weight']) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars($merchant['transaction_count']) ?>

                        </td>

                        <td>

                            $<?= number_format(
                                (float)$merchant['transaction_amount'],
                                2
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- Search Customers -->

    <h2>Search Customers by Risk</h2>

    <form method="GET" class="mb-3">

        <input
            type="text"
            name="search"
            placeholder="Search by customer name or ID"
            value="<?= htmlspecialchars($customerSearch) ?>"
        >

        <select name="risk">

            <option value="">
                All Risk Levels
            </option>

            <option
                value="HIGH"
                <?= $riskFilter === 'HIGH' ? 'selected' : '' ?>
            >
                HIGH
            </option>

            <option
                value="MEDIUM"
                <?= $riskFilter === 'MEDIUM' ? 'selected' : '' ?>
            >
                MEDIUM
            </option>

            <option
                value="LOW"
                <?= $riskFilter === 'LOW' ? 'selected' : '' ?>
            >
                LOW
            </option>

        </select>

        <button
            type="submit"
            class="btn btn-primary btn-sm"
        >
            Search
        </button>

        <a
            href="dashboard.php"
            class="btn btn-secondary btn-sm"
        >
            Clear
        </a>

    </form>


    <!-- Customer Risk Table -->

    <table class="table table-hover table-bordered align-middle mt-3">

        <thead class="table-dark">

            <tr>

                <th>Customer ID</th>

                <th>Customer Name</th>

                <th>Velocity Score</th>

                <th>MCC Score</th>

                <th>Total Risk Score</th>

                <th>Risk Level</th>

            </tr>

        </thead>

        <tbody>

        <?php foreach ($riskCustomers as $customer): ?>

            <tr>

                <td>

                    <?= htmlspecialchars($customer['customer_id']) ?>

                </td>

                <td>

                    <a
                        href="customer_risk.php?customer_id=<?= (int)$customer['customer_id'] ?>"
                    >

                        <?= htmlspecialchars($customer['full_name']) ?>

                    </a>

                </td>

                <td>

                    <?= htmlspecialchars($customer['velocity_score']) ?>

                </td>

                <td>

                    <?= htmlspecialchars($customer['mcc_score']) ?>

                </td>

                <td>

                    <strong>

                        <?= htmlspecialchars($customer['total_score']) ?>

                    </strong>

                </td>

                <td>

                    <?php

                    $riskClass = 'risk-low';

                    if ($customer['risk_band'] === 'HIGH') {

                        $riskClass = 'risk-high';

                    } elseif ($customer['risk_band'] === 'MEDIUM') {

                        $riskClass = 'risk-medium';

                    }

                    ?>

                    <span class="<?= $riskClass ?>">

                        <?= htmlspecialchars($customer['risk_band']) ?>

                    </span>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>


    <!-- Highest Risk Customers -->

    <h2>Highest Risk Customers</h2>

    <table class="table table-hover table-bordered align-middle mt-3">

        <thead class="table-dark">

            <tr>

                <th>Customer ID</th>

                <th>Velocity Score</th>

                <th>Merchant Risk</th>

                <th>Total Score</th>

                <th>Risk Band</th>

            </tr>

        </thead>

        <tbody>

        <?php foreach ($topRiskCustomers as $row): ?>

            <tr>

                <td>

                    <a
                        href="customer_risk.php?customer_id=<?= (int)$row['customer_id'] ?>"
                    >

                        <?= htmlspecialchars($row['customer_id']) ?>

                    </a>

                </td>

                <td>

                    <?= htmlspecialchars($row['velocity_score']) ?>

                </td>

                <td>

                    <?= htmlspecialchars($row['mcc_score']) ?>

                </td>

                <td>

                    <strong>

                        <?= htmlspecialchars($row['total_score']) ?>

                    </strong>

                </td>

                <td>

                    <?php

                    $topRiskClass = 'risk-low';

                    if ($row['risk_band'] === 'HIGH') {

                        $topRiskClass = 'risk-high';

                    } elseif ($row['risk_band'] === 'MEDIUM') {

                        $topRiskClass = 'risk-medium';

                    }

                    ?>

                    <span class="<?= $topRiskClass ?>">

                        <?= htmlspecialchars($row['risk_band']) ?>

                    </span>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>


    <!-- Alert Queue -->

    <h2>Alert Queue</h2>

    <p class="text-muted">

        Review and manage transaction monitoring alerts requiring compliance attention.

    </p>


    <table class="table table-hover table-bordered align-middle mt-3 shadow-sm">

        <thead class="table-dark">

            <tr>

                <th>Alert ID</th>

                <th>Customer</th>

                <th>Rule</th>

                <th>Severity</th>

                <th>Details</th>

                <th>Status</th>

                <th>Action</th>

            </tr>

        </thead>

        <tbody>

        <?php foreach ($alerts as $a): ?>

            <tr>

                <td>

                    #<?= htmlspecialchars($a['alert_id']) ?>

                </td>

                <td>

                    <?= htmlspecialchars($a['full_name']) ?>

                </td>

                <td>

                    <?= htmlspecialchars($a['rule_code']) ?>

                </td>

                <td>

                    <?php

                    $severityClass = 'risk-medium';

                    if ($a['severity'] === 'HIGH') {

                        $severityClass = 'risk-high';

                    }

                    ?>

                    <span class="<?= $severityClass ?>">

                        <?= htmlspecialchars($a['severity']) ?>

                    </span>

                </td>

                <td>

                    <?= htmlspecialchars($a['details']) ?>

                </td>

                <td>

                    <?php

                    $statusClass = 'bg-secondary';

                    if ($a['status'] === 'OPEN') {

                        $statusClass = 'bg-primary';

                    } elseif ($a['status'] === 'UNDER_REVIEW') {

                        $statusClass = 'bg-warning text-dark';

                    } elseif ($a['status'] === 'ESCALATED') {

                        $statusClass = 'bg-danger';

                    } elseif ($a['status'] === 'CLOSED') {

                        $statusClass = 'bg-success';

                    }

                    ?>

                    <span class="badge <?= $statusClass ?>">

                        <?= htmlspecialchars($a['status']) ?>

                    </span>

                </td>

                <td>

                    <form
                        method="post"
                        class="d-flex gap-2"
                    >

                        <input
                            type="hidden"
                            name="alert_id"
                            value="<?= $a['alert_id'] ?>"
                        >

                        <button
                            class="btn btn-sm btn-outline-primary"
                            name="status"
                            value="UNDER_REVIEW"
                        >
                            Review
                        </button>

                        <button
                            class="btn btn-sm btn-outline-danger"
                            name="status"
                            value="ESCALATED"
                        >
                            Escalate
                        </button>

                        <button
                            class="btn btn-sm btn-outline-success"
                            name="status"
                            value="CLOSED"
                        >
                            Close
                        </button>

                    </form>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>


</div>


<!-- =========================
     CHART.JS
========================== -->

<script>

    /*
     * Customer Risk Distribution
     */

    const riskChart = document.getElementById('riskChart');

    new Chart(riskChart, {

        type: 'doughnut',

        data: {

            labels: [
                'HIGH',
                'MEDIUM',
                'LOW'
            ],

            datasets: [{

                data: [
                    <?= $riskChart['HIGH'] ?>,
                    <?= $riskChart['MEDIUM'] ?>,
                    <?= $riskChart['LOW'] ?>
                ],

                backgroundColor: [
                    '#dc3545',
                    '#f0ad4e',
                    '#28a745'
                ]

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    position: 'bottom'

                }

            }

        }

    });


    /*
     * Alert Status Chart
     */

    const alertChart = document.getElementById('alertChart');

    new Chart(alertChart, {

        type: 'bar',

        data: {

            labels: [
                'OPEN',
                'UNDER REVIEW',
                'ESCALATED',
                'CLOSED'
            ],

            datasets: [{

                label: 'Alerts',

                data: [
                    <?= $alertStatusChart['OPEN'] ?>,
                    <?= $alertStatusChart['UNDER_REVIEW'] ?>,
                    <?= $alertStatusChart['ESCALATED'] ?>,
                    <?= $alertStatusChart['CLOSED'] ?>
                ],

                backgroundColor: [
                    '#0d6efd',
                    '#ffc107',
                    '#dc3545',
                    '#198754'
                ]

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

            },

            plugins: {

                legend: {

                    display: false

                }

            }

        }

    });


    /*
     * Merchant Risk Activity
     */

    const merchantChart = document.getElementById('merchantChart');

    new Chart(merchantChart, {

        type: 'bar',

        data: {

            labels: [

                <?php foreach ($merchantRows as $merchant): ?>

<?= json_encode($merchant['category_name']) ?>,
                <?php endforeach; ?>

            ],

            datasets: [{

                label: 'Transactions',

                data: [

                    <?php foreach ($merchantRows as $merchant): ?>

                        <?= (int)$merchant['transaction_count'] ?>,

                    <?php endforeach; ?>

                ],

                backgroundColor: '#6f42c1'

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

            },

            plugins: {

                legend: {

                    display: false

                }

            }

        }

    });

</script>

</body>

</html>
