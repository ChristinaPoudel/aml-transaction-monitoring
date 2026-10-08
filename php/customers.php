<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';

$pdo = db();


/* =========================
   CUSTOMER RISK LIST
========================= */

$stmt = $pdo->query(
    "SELECT
        c.customer_id,
        c.full_name,
        COALESCE(rs.total_score, 0) AS risk_score,
        COALESCE(rs.risk_band, 'LOW') AS risk_band,
        COUNT(t.txn_id) AS transaction_count,
        COALESCE(SUM(t.amount), 0) AS transaction_amount

     FROM customers c

     LEFT JOIN risk_scores rs
        ON rs.customer_id = c.customer_id
        AND rs.score_date = CURRENT_DATE

     LEFT JOIN accounts a
        ON a.customer_id = c.customer_id

     LEFT JOIN transactions t
        ON t.account_id = a.account_id

     GROUP BY
        c.customer_id,
        c.full_name,
        rs.total_score,
        rs.risk_band

     ORDER BY
        COALESCE(rs.total_score, 0) DESC,
        c.customer_id ASC"
);

$customers = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Risk List</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

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

        .content-card {
            background: white;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px;
        }

        .section-title {
            font-size: 21px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .section-description {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 20px;
        }

        table {
            margin-bottom: 0 !important;
        }

        .table th {
            background: #f8fafc;
            font-size: 14px;
            white-space: nowrap;
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

        .back-link {
            margin-bottom: 20px;
        }

    </style>

</head>

<body>


<!-- =========================
     HEADER
========================= -->

<div class="page-header">

    <div class="container">

        <h1>Customer Risk List</h1>

        <p>Customer monitoring and risk assessment</p>

    </div>

</div>


<div class="container">


    <!-- Back to Analytics -->

    <div class="back-link">

        <a href="analytics.php" class="btn btn-outline-secondary">

            ← Back to Analytics

        </a>

    </div>


    <!-- Customer Table -->

    <div class="content-card">

        <div class="section-title">

            All Customers

        </div>

        <div class="section-description">

            <?= count($customers) ?> customers currently monitored by the AML system.

        </div>


        <div class="table-responsive">

            <table class="table table-hover">

                <thead>

                    <tr>

                        <th>Customer ID</th>

                        <th>Customer</th>

                        <th>Risk Score</th>

                        <th>Risk Band</th>

                        <th>Transactions</th>

                        <th>Transaction Amount</th>

                        <th>Profile</th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($customers as $customer): ?>

                        <tr>

                            <td>

                                <?= (int)$customer['customer_id'] ?>

                            </td>


                            <td>

                                <strong>

                                    <?= htmlspecialchars($customer['full_name']) ?>

                                </strong>

                            </td>


                            <td class="score">

                                <?= (int)$customer['risk_score'] ?>

                            </td>


                            <td>

                                <?php if ($customer['risk_band'] === 'HIGH'): ?>

                                    <span class="risk-high">

                                        HIGH

                                    </span>

                                <?php elseif ($customer['risk_band'] === 'MEDIUM'): ?>

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

                                <?= (int)$customer['transaction_count'] ?>

                            </td>


                            <td>

                                $<?= number_format(
                                    (float)$customer['transaction_amount'],
                                    2
                                ) ?>

                            </td>


                            <td>

                                <a
                                    href="customer_risk.php?customer_id=<?= (int)$customer['customer_id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >

                                    View Profile

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>


</div>

</body>

</html>