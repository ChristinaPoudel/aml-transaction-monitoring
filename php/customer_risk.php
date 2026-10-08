<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';

$pdo = db();

$customerId = (int)($_GET['customer_id'] ?? 0);

if ($customerId <= 0) {
    die('Invalid customer ID.');
}


/*
 * Handle customer name update
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {

    $fullName = trim($_POST['full_name'] ?? '');

    if ($fullName !== '') {

        $updateCustomer = $pdo->prepare(
            "UPDATE customers
             SET full_name = :full_name
             WHERE customer_id = :customer_id"
        );

        $updateCustomer->execute([
            'full_name'   => $fullName,
            'customer_id' => $customerId
        ]);
    }

    header(
        'Location: customer_risk.php?customer_id=' .
        $customerId .
        '&updated=1'
    );

    exit;
}


/*
 * Handle account creation
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {

    $accountNumber = trim($_POST['account_number'] ?? '');
    $accountType   = trim($_POST['account_type'] ?? 'CHECKING');

    if ($accountNumber !== '') {

        $accountStmt = $pdo->prepare(
            "INSERT INTO accounts
                (customer_id, account_number, account_type)
             VALUES
                (:customer_id, :account_number, :account_type)"
        );

        $accountStmt->execute([
            'customer_id'    => $customerId,
            'account_number' => $accountNumber,
            'account_type'   => $accountType
        ]);
    }

    header(
        'Location: customer_risk.php?customer_id=' .
        $customerId .
        '&account_added=1'
    );

    exit;
}


/*
 * Get customer information and risk score
 */
$stmt = $pdo->prepare(
    "SELECT
        c.customer_id,
        c.full_name,
        COALESCE(r.velocity_score, 0) AS velocity_score,
        COALESCE(r.mcc_score, 0) AS mcc_score,
        COALESCE(r.total_score, 0) AS total_score,
        COALESCE(r.risk_band, 'LOW') AS risk_band
     FROM customers c
     LEFT JOIN risk_scores r
        ON c.customer_id = r.customer_id
        AND r.score_date = CURRENT_DATE
     WHERE c.customer_id = :customer_id"
);

$stmt->execute([
    'customer_id' => $customerId
]);

$customer = $stmt->fetch();

if (!$customer) {
    die('Customer not found.');
}


/*
 * Get customer accounts
 */
$accountStmt = $pdo->prepare(
    "SELECT
        account_id,
        account_number,
        account_type
     FROM accounts
     WHERE customer_id = :customer_id
     ORDER BY account_id"
);

$accountStmt->execute([
    'customer_id' => $customerId
]);

$accounts = $accountStmt->fetchAll();


/*
 * Get customer transactions
 */
$txnStmt = $pdo->prepare(
    "SELECT
        t.txn_id,
        t.amount,
        t.txn_type,
        t.txn_time,
        mt.category_name,
        g.country_code
     FROM transactions t
     JOIN accounts a
        ON t.account_id = a.account_id
     LEFT JOIN merchant_types mt
        ON t.merchant_type_id = mt.merchant_type_id
     LEFT JOIN geo_locations g
        ON t.geo_id = g.geo_id
     WHERE a.customer_id = :customer_id
     ORDER BY t.txn_time DESC"
);

$txnStmt->execute([
    'customer_id' => $customerId
]);

$transactions = $txnStmt->fetchAll();


/*
 * Calculate transaction totals
 */
$totalTransactions = count($transactions);

$totalAmount = 0;

foreach ($transactions as $transaction) {

    $totalAmount += (float)$transaction['amount'];
}


/*
 * Identify risk factors
 */
$riskFactors = [];

if ((int)$customer['velocity_score'] >= 20) {

    $riskFactors[] = 'High transaction activity';
}

if ((int)$customer['velocity_score'] >= 40) {

    $riskFactors[] = 'Very high transaction velocity';
}

if ((int)$customer['mcc_score'] >= 15) {

    $riskFactors[] = 'Multiple risky merchant activities';
}


foreach ($transactions as $transaction) {

    if (($transaction['category_name'] ?? '') === 'Gambling') {

        $riskFactors[] = 'Gambling-related transactions';

        break;
    }
}


foreach ($transactions as $transaction) {

    if (($transaction['country_code'] ?? '') === 'PRK') {

        $riskFactors[] = 'High-risk geographic activity';

        break;
    }
}


if ($totalAmount >= 50000) {

    $riskFactors[] = 'High total transaction amount';
}


/*
 * Risk display class
 */
$riskClass = 'risk-low';

if ($customer['risk_band'] === 'HIGH') {

    $riskClass = 'risk-high';

} elseif ($customer['risk_band'] === 'MEDIUM') {

    $riskClass = 'risk-medium';
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Customer Risk -
        <?= htmlspecialchars($customer['full_name'] ?? 'Customer') ?>
    </title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    >

    <style>

    /* ================================
       GENERAL PAGE
    ================================= */

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: #eef3f8;
        color: #1e293b;
        font-family: "Segoe UI", Arial, sans-serif;
    }

    .container {
        max-width: 1250px;
    }

    h1 {
        font-size: 30px;
        font-weight: 700;
        color: #0f2747;
        margin-bottom: 5px;
    }

    h2 {
        font-size: 21px;
        font-weight: 700;
        color: #17365d;
        margin-top: 35px;
        margin-bottom: 15px;
    }

    h3 {
        font-weight: 600;
    }

    a {
        text-decoration: none;
    }


    /* ================================
       TOP HEADER
    ================================= */

    .page-header {
        background: linear-gradient(
            135deg,
            #0f2747,
            #173f6d
        );

        color: white;

        padding: 25px 30px;

        border-radius: 14px;

        box-shadow:
            0 8px 25px rgba(15, 39, 71, 0.18);

        margin-bottom: 25px;
    }

    .page-header h1 {
        color: white;
        margin: 0;
        font-size: 28px;
    }

    .page-header p {
        color: rgba(255, 255, 255, 0.75);
        margin-top: 5px;
    }

    .page-header .btn {
        border-color: rgba(255, 255, 255, 0.5);
        color: white;
    }

    .page-header .btn:hover {
        background: white;
        color: #17365d;
    }


    /* ================================
       CUSTOMER PROFILE
    ================================= */

    .profile-card {
        background: white;

        border: 1px solid #e1e8f0;

        border-radius: 14px;

        padding: 25px;

        box-shadow:
            0 4px 15px rgba(15, 39, 71, 0.07);

        margin-bottom: 20px;

        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease;
    }

    .profile-card:hover {
        transform: translateY(-1px);

        box-shadow:
            0 7px 20px rgba(15, 39, 71, 0.10);
    }

    .profile-card h2 {
        font-size: 25px;
        margin: 0 0 5px 0;
        color: #102f52;
    }

    .customer-id {
        color: #718096;
        font-size: 14px;
    }


    /* ================================
       BUTTONS
    ================================= */

    .btn {
        border-radius: 8px;
        font-weight: 600;
        padding: 9px 16px;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background: #1769aa;
        border-color: #1769aa;
    }

    .btn-primary:hover {
        background: #125587;
        border-color: #125587;
        transform: translateY(-1px);
    }

    .btn-success {
        background: #16855b;
        border-color: #16855b;
    }

    .btn-success:hover {
        background: #116b49;
        border-color: #116b49;
        transform: translateY(-1px);
    }

    .btn-outline-secondary {
        border-width: 1px;
    }


    /* ================================
       RISK OVERVIEW
    ================================= */

    .main-risk-card,
    .info-card {
        background: white;

        border: 1px solid #e1e8f0;

        border-radius: 14px;

        padding: 24px;

        height: 100%;

        box-shadow:
            0 4px 15px rgba(15, 39, 71, 0.07);

        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease;
    }

    .main-risk-card:hover,
    .info-card:hover {
        transform: translateY(-3px);

        box-shadow:
            0 9px 25px rgba(15, 39, 71, 0.12);
    }

    .main-risk-card {
        border-left: 6px solid #16855b;
    }

    .main-risk-card.high {
        border-left-color: #dc3545;
    }

    .main-risk-card.medium {
        border-left-color: #e39a20;
    }

    .main-risk-card.low {
        border-left-color: #16855b;
    }

    .main-risk-card h3,
    .info-card h3 {
        font-size: 14px;

        text-transform: uppercase;

        letter-spacing: 0.5px;

        color: #718096;

        margin-bottom: 12px;
    }

    .risk-score {
        font-size: 45px;

        line-height: 1;

        font-weight: 750;

        color: #102f52;

        margin-bottom: 15px;
    }

    .info-card .value {
        font-size: 34px;

        line-height: 1;

        font-weight: 750;

        color: #102f52;

        margin: 12px 0;
    }

    .info-card p {
        color: #718096;
        font-size: 14px;
    }


    /* ================================
       RISK BADGES
    ================================= */

    .risk-high,
    .risk-medium,
    .risk-low {
        display: inline-flex;

        align-items: center;

        padding: 7px 14px;

        border-radius: 20px;

        font-size: 12px;

        font-weight: 700;

        letter-spacing: 0.5px;
    }

    .risk-high {
        color: #991b1b;
        background: #fee2e2;
        border: 1px solid #fecaca;
    }

    .risk-medium {
        color: #92400e;
        background: #fef3c7;
        border: 1px solid #fde68a;
    }

    .risk-low {
        color: #166534;
        background: #dcfce7;
        border: 1px solid #bbf7d0;
    }


    /* ================================
       SECTION TITLES
    ================================= */

    h2::before {
        content: "";
        display: inline-block;

        width: 4px;
        height: 20px;

        background: #1769aa;

        border-radius: 5px;

        margin-right: 9px;

        vertical-align: -3px;
    }


    /* ================================
       ACCOUNT SECTION
    ================================= */

    .profile-card .table {
        margin-bottom: 20px;
    }

    .table {
        border-color: #e2e8f0;
    }

    .table thead th {
        background: #17365d;
        color: white;

        font-size: 13px;

        font-weight: 600;

        padding: 13px;

        border-color: #17365d;
    }

    .table tbody td {
        padding: 13px;

        color: #334155;

        font-size: 14px;

        vertical-align: middle;
    }

    .table tbody tr {
        transition: background 0.15s ease;
    }

    .table tbody tr:hover {
        background: #f1f7fc;
    }


    /* ================================
       EMPTY SECTIONS
    ================================= */

    .empty-box {
        background: #f8fafc;

        border: 1px dashed #b8c5d3;

        border-radius: 10px;

        padding: 28px 20px;

        text-align: center;

        color: #64748b;

        margin-bottom: 15px;
    }

    .empty-box h5 {
        color: #334155;

        font-weight: 650;

        margin-bottom: 7px;
    }

    .empty-box p {
        font-size: 14px;

        margin-bottom: 0;
    }


    /* ================================
       FORM CONTROLS
    ================================= */

    .form-label {
        font-weight: 600;

        color: #334155;

        font-size: 14px;
    }

    .form-control,
    .form-select {
        border: 1px solid #cbd5e1;

        border-radius: 8px;

        padding: 10px 12px;

        font-size: 14px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #1769aa;

        box-shadow:
            0 0 0 3px rgba(23, 105, 170, 0.12);
    }


    /* ================================
       RISK FACTORS
    ================================= */

    .risk-factor {
        background: white;

        border: 1px solid #f0d1d1;

        border-left: 5px solid #dc3545;

        padding: 14px 17px;

        margin-bottom: 10px;

        border-radius: 8px;

        color: #7f1d1d;

        font-size: 14px;

        box-shadow:
            0 2px 8px rgba(0, 0, 0, 0.04);
    }


    /* ================================
       SUCCESS MESSAGE
    ================================= */

    .alert {
        border-radius: 9px;

        border-width: 1px;

        font-size: 14px;
    }


    /* ================================
       TRANSACTION TABLE
    ================================= */

    .table-responsive {
        background: white;

        border-radius: 12px;

        box-shadow:
            0 4px 15px rgba(15, 39, 71, 0.07);

        overflow: hidden;
    }

    .table-responsive .table {
        margin-bottom: 0;
    }


    /* ================================
       PAGE SPACING
    ================================= */

    .section-spacing {
        margin-top: 30px;
    }


    /* ================================
       MOBILE
    ================================= */

    @media (max-width: 768px) {

        .container {
            padding-left: 15px;
            padding-right: 15px;
        }

        .page-header {
            padding: 20px;
        }

        .page-header h1 {
            font-size: 23px;
        }

        .profile-card {
            padding: 18px;
        }

        .profile-card .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 15px;
        }

        .risk-score {
            font-size: 38px;
        }

        .info-card .value {
            font-size: 30px;
        }

        h2 {
            font-size: 19px;
        }

    }

</style>

</head>

<body>

<div class="container py-4">


    <!-- Header -->

   <div class="page-header d-flex justify-content-between align-items-center">

        <div>

            <h1>
                Customer Risk Profile
            </h1>

            <p class="text-muted mb-0">
                AML customer risk assessment and transaction history
            </p>

        </div>

        <div>

            <a
                href="dashboard.php"
                class="btn btn-outline-secondary"
            >
                ← Back to Dashboard
            </a>

        </div>

    </div>


    <!-- Success Messages -->

    <?php if (isset($_GET['updated'])): ?>

        <div class="alert alert-success">
            Customer information updated successfully.
        </div>

    <?php endif; ?>


    <?php if (isset($_GET['account_added'])): ?>

        <div class="alert alert-success">
            Account added successfully.
        </div>

    <?php endif; ?>


    <!-- Customer Information -->

    <div class="profile-card">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h2 class="mt-0 mb-1">

                    <?= htmlspecialchars(
                        $customer['full_name'] ?? 'Unnamed Customer'
                    ) ?>

                </h2>

                <p class="text-muted mb-0">

                    Customer ID:

                    <strong>
                        <?= htmlspecialchars(
                            (string)$customer['customer_id']
                        ) ?>
                    </strong>

                </p>

            </div>

            <button
                class="btn btn-primary"
                data-bs-toggle="collapse"
                data-bs-target="#editCustomer"
            >
                Edit Customer
            </button>

        </div>


        <div
            class="collapse mt-4"
            id="editCustomer"
        >

            <form method="post">

                <div class="row g-3 align-items-end">

                    <div class="col-md-8">

                        <label class="form-label">
                            Customer Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $customer['full_name'] ?? ''
                            ) ?>"
                            required
                        >

                    </div>

                    <div class="col-md-4">

                        <button
                            type="submit"
                            name="update_customer"
                            value="1"
                            class="btn btn-success w-100"
                        >
                            Save Changes
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- Risk Overview -->

    <h2>
        Risk Overview
    </h2>

    <div class="row g-4">


        <!-- Risk Score -->

        <div class="col-md-4">

            <div class="main-risk-card <?= $riskClass ?>">

                <h3>
                    Overall Risk
                </h3>

                <div class="risk-score">

                    <?= htmlspecialchars(
                        (string)$customer['total_score']
                    ) ?>

                </div>

                <div class="mt-2">

                    <span class="<?= $riskClass ?>">

                        <?= htmlspecialchars(
                            $customer['risk_band'] ?? 'LOW'
                        ) ?>

                    </span>

                </div>

            </div>

        </div>


        <!-- Velocity -->

        <div class="col-md-4">

            <div class="info-card">

                <h3>
                    Velocity Score
                </h3>

                <div class="value">

                    <?= htmlspecialchars(
                        (string)$customer['velocity_score']
                    ) ?>

                </div>

                <p class="text-muted mb-0">

                    Transaction activity score

                </p>

            </div>

        </div>


        <!-- Merchant Risk -->

        <div class="col-md-4">

            <div class="info-card">

                <h3>
                    Merchant Risk Score
                </h3>

                <div class="value">

                    <?= htmlspecialchars(
                        (string)$customer['mcc_score']
                    ) ?>

                </div>

                <p class="text-muted mb-0">

                    Risk from merchant activity

                </p>

            </div>

        </div>

    </div>


    <!-- Account Information -->

    <h2>
        Account Information
    </h2>

    <div class="profile-card">

        <?php if (count($accounts) > 0): ?>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>Account ID</th>

                            <th>Account Number</th>

                            <th>Account Type</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($accounts as $account): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    (string)$account['account_id']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $account['account_number'] ?? ''
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $account['account_type'] ?? ''
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-box mb-3">

                <h5>
                    No account added yet
                </h5>

                <p class="mb-0">
                    Add an account to begin recording this customer's
                    transaction activity.
                </p>

            </div>

        <?php endif; ?>


        <button
            class="btn btn-success"
            data-bs-toggle="collapse"
            data-bs-target="#addAccount"
        >
            + Add Account
        </button>


        <div
            class="collapse mt-4"
            id="addAccount"
        >

            <form method="post">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Account Number
                        </label>

                        <input
                            type="text"
                            name="account_number"
                            class="form-control"
                            placeholder="Example: ACC1064"
                            required
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Account Type
                        </label>

                        <select
                            name="account_type"
                            class="form-select"
                        >

                            <option value="CHECKING">
                                CHECKING
                            </option>

                            <option value="SAVINGS">
                                SAVINGS
                            </option>

                        </select>

                    </div>

                    <div class="col-md-2 d-flex align-items-end">

                        <button
                            type="submit"
                            name="add_account"
                            value="1"
                            class="btn btn-primary w-100"
                        >
                            Add
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- Transaction Summary -->

    <h2>
        Transaction Summary
    </h2>

    <div class="row g-4">


        <div class="col-md-6">

            <div class="info-card">

                <h3>
                    Total Transactions
                </h3>

                <div class="value">

                    <?= $totalTransactions ?>

                </div>

                <p class="text-muted mb-0">

                    Transactions recorded

                </p>

            </div>

        </div>


        <div class="col-md-6">

            <div class="info-card">

                <h3>
                    Total Transaction Amount
                </h3>

                <div class="value">

                    $<?= number_format($totalAmount, 2) ?>

                </div>

                <p class="text-muted mb-0">

                    Combined transaction value

                </p>

            </div>

        </div>

    </div>


    <!-- Risk Factors -->

    <h2>
        Risk Factors
    </h2>

    <?php if (count($riskFactors) > 0): ?>

        <?php foreach ($riskFactors as $factor): ?>

            <div class="risk-factor">

                ⚠️

                <?= htmlspecialchars($factor) ?>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="alert alert-success">

            No significant risk factors identified by the current
            rule-based model.

        </div>

    <?php endif; ?>


    <!-- Transaction History -->

    <h2>
        Transaction History
    </h2>

    <?php if (count($transactions) > 0): ?>

        <div class="table-responsive">

            <table class="table table-hover table-bordered align-middle bg-white">

                <thead class="table-dark">

                    <tr>

                        <th>Transaction ID</th>

                        <th>Amount</th>

                        <th>Merchant Type</th>

                        <th>Country</th>

                        <th>Transaction Type</th>

                        <th>Date / Time</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($transactions as $transaction): ?>

                    <tr>

                        <td>

                            #<?= htmlspecialchars(
                                (string)$transaction['txn_id']
                            ) ?>

                        </td>

                        <td>

                            <strong>

                                $<?= number_format(
                                    (float)$transaction['amount'],
                                    2
                                ) ?>

                            </strong>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $transaction['category_name']
                                ?? 'Unknown'
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $transaction['country_code']
                                ?? 'Unknown'
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $transaction['txn_type']
                                ?? 'Unknown'
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $transaction['txn_time']
                                ?? 'Unknown'
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="empty-box">

            <h5>
                No transactions yet
            </h5>

            <p class="mb-0">
                Transactions can be added after an account has been created.
            </p>

        </div>

    <?php endif; ?>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>