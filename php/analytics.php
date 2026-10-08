<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';

$pdo = db();

/* =========================
   SYSTEM OVERVIEW
========================= */

$totalCustomers = (int)$pdo->query(
    "SELECT COUNT(*) FROM customers"
)->fetchColumn();

$totalTransactions = (int)$pdo->query(
    "SELECT COUNT(*) FROM transactions"
)->fetchColumn();

$totalAmount = (float)$pdo->query(
    "SELECT COALESCE(SUM(amount), 0) FROM transactions"
)->fetchColumn();

$totalAlerts = (int)$pdo->query(
    "SELECT COUNT(*) FROM alerts"
)->fetchColumn();


/* =========================
   RISK SUMMARY
========================= */

$riskSummary = $pdo->query(
    "SELECT risk_band, COUNT(*) AS customer_count
     FROM risk_scores
     WHERE score_date = CURRENT_DATE
     GROUP BY risk_band
     ORDER BY
        CASE risk_band
            WHEN 'HIGH' THEN 1
            WHEN 'MEDIUM' THEN 2
            WHEN 'LOW' THEN 3
        END"
)->fetchAll();


/* =========================
   ALERT STATUS
========================= */

$alertStatus = $pdo->query(
    "SELECT status, COUNT(*) AS alert_count
     FROM alerts
     GROUP BY status
     ORDER BY status"
)->fetchAll();


/* =========================
   ALERT SEVERITY
========================= */

$alertSeverity = $pdo->query(
    "SELECT severity, COUNT(*) AS alert_count
     FROM alerts
     GROUP BY severity
     ORDER BY severity"
)->fetchAll();


/* =========================
   MERCHANT RISK
========================= */

$merchantRisk = $pdo->query(
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
     ORDER BY mt.risk_weight DESC"
)->fetchAll();


/* =========================
   HIGHEST RISK CUSTOMERS
========================= */

$topCustomers = $pdo->query(
    "SELECT
        rs.customer_id,
        c.full_name,
        rs.total_score,
        rs.risk_band
     FROM risk_scores rs
     JOIN customers c
        ON c.customer_id = rs.customer_id
     WHERE rs.score_date = CURRENT_DATE
     ORDER BY rs.total_score DESC
     LIMIT 5"
)->fetchAll();


/* =========================
   CHART DATA
========================= */

$riskLabels = [];
$riskCounts = [];

foreach ($riskSummary as $row) {
    $riskLabels[] = $row['risk_band'];
    $riskCounts[] = (int)$row['customer_count'];
}


$alertStatusLabels = [];
$alertStatusCounts = [];

foreach ($alertStatus as $row) {
    $alertStatusLabels[] = $row['status'];
    $alertStatusCounts[] = (int)$row['alert_count'];
}


$merchantLabels = [];
$merchantTransactionCounts = [];

foreach ($merchantRisk as $row) {
    $merchantLabels[] = $row['category_name'];
    $merchantTransactionCounts[] = (int)$row['transaction_count'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AML Analytics</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        body {
            background: #f5f7fa;
            color: #212529;
        }

        .page-header {
            background: #1f2937;
            color: white;
            padding: 24px 0;
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin-bottom: 5px;
            font-size: 28px;
        }

        .page-header p {
            margin: 0;
            color: #cbd5e1;
        }

        .section-title {
            font-size: 21px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .summary-card {
            border: none;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px;
            height: 100%;
        }

        .summary-label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 7px;
        }

        .summary-value {
            font-size: 28px;
            font-weight: 700;
        }

        .content-card {
            background: white;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px;
            margin-bottom: 20px;
        }

        .model-box {
            border-left: 4px solid #374151;
            background: #f8fafc;
            padding: 15px 18px;
            margin-bottom: 15px;
        }

        .model-box h5 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .model-box p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .chart-box {
            background: white;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 18px;
            height: 390px;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .chart-container {
            position: relative;
            height: 320px;
        }

        table {
            margin-bottom: 0 !important;
        }

        .table th {
            background: #f8fafc;
            font-size: 14px;
        }

        .table td {
            font-size: 14px;
            vertical-align: middle;
        }

        .risk-high {
            color: #b91c1c;
            font-weight: 700;
        }

        .risk-medium {
            color: #b45309;
            font-weight: 700;
        }

        .risk-low {
            color: #15803d;
            font-weight: 700;
        }

        .score {
            font-weight: 700;
        }

        .footer-note {
            color: #6b7280;
            font-size: 13px;
            padding: 10px 0 30px;
        }

    </style>

</head>

<body>


<!-- =========================
     HEADER
========================= -->

<div class="page-header">

    <div class="container">

        <h1>AML Analytics</h1>

        <p>Transaction monitoring and risk analytics</p>

    </div>

</div>


<div class="container">


<!-- =========================
     SYSTEM OVERVIEW
========================= -->

<div class="section-title">
    System Overview
</div>

<div class="row g-3 mb-4">

    <div class="col-md-3">

    <a href="customers.php" class="text-decoration-none text-dark">

        <div class="summary-card">

            <div class="summary-label">
                Total Customers
            </div>

            <div class="summary-value">
                <?= $totalCustomers ?>
            </div>

            <div class="small text-primary mt-2">
                View all customers →
            </div>

        </div>

    </a>

</div>


    <div class="col-md-3">

        <div class="summary-card">

            <div class="summary-label">
                Total Transactions
            </div>

            <div class="summary-value">
                <?= $totalTransactions ?>
            </div>

        </div>

    </div>


    <div class="col-md-3">

        <div class="summary-card">

            <div class="summary-label">
                Transaction Volume
            </div>

            <div class="summary-value">
                $<?= number_format($totalAmount, 2) ?>
            </div>

        </div>

    </div>


    <div class="col-md-3">

        <div class="summary-card">

            <div class="summary-label">
                Total Alerts
            </div>

            <div class="summary-value">
                <?= $totalAlerts ?>
            </div>

        </div>

    </div>

</div>


<!-- =========================
     RISK SCORING MODEL
========================= -->

<div class="content-card">

    <div class="section-title">
        Risk Scoring Model
    </div>

    <p>
        This system uses a rule-based customer risk scoring model
        to identify customers who may require additional compliance review.
    </p>


    <div class="row g-3">

        <div class="col-md-4">

            <div class="model-box">

                <h5>Velocity Score</h5>

                <p>
                    Measures recent transaction activity using transaction
                    frequency and transaction amount.
                </p>

            </div>

        </div>


        <div class="col-md-4">

            <div class="model-box">

                <h5>Merchant Risk Score</h5>

                <p>
                    Adds risk points when transactions involve
                    higher-risk merchant categories.
                </p>

            </div>

        </div>


        <div class="col-md-4">

            <div class="model-box">

                <h5>Total Risk Score</h5>

                <p>
                    Combines the velocity score and merchant risk score
                    to produce the customer's total risk score.
                </p>

            </div>

        </div>

    </div>


    <div class="mt-2">

        <strong>Risk Bands:</strong>

        LOW: 0–44 &nbsp; | &nbsp;
        MEDIUM: 45–59 &nbsp; | &nbsp;
        HIGH: 60+

    </div>

</div>


<!-- =========================
     VISUAL ANALYTICS
========================= -->

<div class="section-title mt-4">
    Visual Analytics
</div>


<div class="row g-3 mb-2">


    <!-- Risk Chart -->

    <div class="col-lg-4">

        <div class="chart-box">

            <div class="chart-title">
                Customer Risk Distribution
            </div>

            <div class="chart-container">

                <canvas id="riskChart"></canvas>

            </div>

        </div>

    </div>


    <!-- Alert Status -->

    <div class="col-lg-4">

        <div class="chart-box">

            <div class="chart-title">
                Alert Status
            </div>

            <div class="chart-container">

                <canvas id="alertStatusChart"></canvas>

            </div>

        </div>

    </div>


    <!-- Merchant Risk -->

    <div class="col-lg-4">

        <div class="chart-box">

            <div class="chart-title">
                Merchant Risk Activity
            </div>

            <div class="chart-container">

                <canvas id="merchantChart"></canvas>

            </div>

        </div>

    </div>

</div>


<!-- =========================
     CUSTOMER RISK DISTRIBUTION
========================= -->

<div class="content-card">

    <div class="section-title">
        Customer Risk Distribution
    </div>

    <div class="table-responsive">

        <table class="table table-hover">

            <thead>

                <tr>
                    <th>Risk Band</th>
                    <th>Customer Count</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($riskSummary as $row): ?>

                    <tr>

                        <td>

                            <?php if ($row['risk_band'] === 'HIGH'): ?>

                                <span class="risk-high">
                                    HIGH
                                </span>

                            <?php elseif ($row['risk_band'] === 'MEDIUM'): ?>

                                <span class="risk-medium">
                                    MEDIUM
                                </span>

                            <?php else: ?>

                                <span class="risk-low">
                                    LOW
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= (int)$row['customer_count'] ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>


<!-- =========================
     ALERT INFORMATION
========================= -->

<div class="row g-3">


    <!-- Alert Status -->

    <div class="col-lg-6">

        <div class="content-card">

            <div class="section-title">
                Alert Status
            </div>

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>

                        <tr>
                            <th>Status</th>
                            <th>Alert Count</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($alertStatus as $row): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($row['status']) ?>
                                </td>

                                <td>
                                    <?= (int)$row['alert_count'] ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- Alert Severity -->

    <div class="col-lg-6">

        <div class="content-card">

            <div class="section-title">
                Alert Severity
            </div>

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>

                        <tr>
                            <th>Severity</th>
                            <th>Alert Count</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($alertSeverity as $row): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($row['severity']) ?>
                                </td>

                                <td>
                                    <?= (int)$row['alert_count'] ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- =========================
     MERCHANT RISK
========================= -->

<div class="content-card">

    <div class="section-title">
        Merchant Risk Analysis
    </div>

    <div class="table-responsive">

        <table class="table table-hover">

            <thead>

                <tr>

                    <th>Merchant Category</th>

                    <th>Risk Weight</th>

                    <th>Transactions</th>

                    <th>Transaction Amount</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($merchantRisk as $row): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($row['category_name']) ?>
                        </td>

                        <td>
                            <?= (int)$row['risk_weight'] ?>
                        </td>

                        <td>
                            <?= (int)$row['transaction_count'] ?>
                        </td>

                        <td>
                            $<?= number_format((float)$row['transaction_amount'], 2) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>


<!-- =========================
     HIGHEST RISK CUSTOMERS
========================= -->

<div class="content-card">

    <div class="section-title">
        Highest Risk Customers
    </div>

    <div class="table-responsive">

        <table class="table table-hover">

            <thead>

                <tr>

                    <th>Customer ID</th>

                    <th>Customer</th>

                    <th>Risk Score</th>

                    <th>Risk Band</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($topCustomers as $row): ?>

                    <tr>

                        <td>
                            <?= (int)$row['customer_id'] ?>
                        </td>

                        <td>

                            <a href="customer_risk.php?customer_id=<?= (int)$row['customer_id'] ?>">

                                <?= htmlspecialchars($row['full_name']) ?>

                            </a>

                        </td>

                        <td class="score">

                            <?= (int)$row['total_score'] ?>

                        </td>

                        <td>

                            <?php if ($row['risk_band'] === 'HIGH'): ?>

                                <span class="risk-high">
                                    HIGH
                                </span>

                            <?php else: ?>

                                <span class="risk-medium">
                                    MEDIUM
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>


<div class="footer-note">

    AML Transaction Monitoring System · Rule-based risk analytics

</div>


</div>


<!-- =========================
     CHARTS
========================= -->

<script>

const riskLabels = <?= json_encode($riskLabels) ?>;

const riskCounts = <?= json_encode($riskCounts) ?>;


new Chart(
    document.getElementById('riskChart'),
    {
        type: 'doughnut',

        data: {
            labels: riskLabels,

            datasets: [
                {
                    label: 'Customers',

                    data: riskCounts
                }
            ]
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
    }
);


const alertStatusLabels =
    <?= json_encode($alertStatusLabels) ?>;

const alertStatusCounts =
    <?= json_encode($alertStatusCounts) ?>;


new Chart(
    document.getElementById('alertStatusChart'),
    {
        type: 'bar',

        data: {
            labels: alertStatusLabels,

            datasets: [
                {
                    label: 'Alerts',

                    data: alertStatusCounts
                }
            ]
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
    }
);


const merchantLabels =
    <?= json_encode($merchantLabels) ?>;

const merchantTransactionCounts =
    <?= json_encode($merchantTransactionCounts) ?>;


new Chart(
    document.getElementById('merchantChart'),
    {
        type: 'bar',

        data: {
            labels: merchantLabels,

            datasets: [
                {
                    label: 'Transactions',

                    data: merchantTransactionCounts
                }
            ]
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
    }
);

</script>


</body>

</html>