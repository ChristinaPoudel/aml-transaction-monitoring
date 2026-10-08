<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';

$pdo = db();

$message = '';
$messageType = '';


/* =========================
   ADD CUSTOMER
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'add') {

    $fullName = trim($_POST['full_name'] ?? '');

    if ($fullName === '') {

        $message = 'Please enter a customer name.';
        $messageType = 'danger';

    } else {

        $stmt = $pdo->prepare(
            "INSERT INTO customers (full_name)
             VALUES (:full_name)"
        );

        $stmt->execute([
            'full_name' => $fullName
        ]);

        $message = 'Customer added successfully.';
        $messageType = 'success';
    }
}


/* =========================
   EDIT CUSTOMER
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'edit') {

    $customerId = (int)($_POST['customer_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');

    if ($customerId <= 0 || $fullName === '') {

        $message = 'Please enter a valid customer name.';
        $messageType = 'danger';

    } else {

        $stmt = $pdo->prepare(
            "UPDATE customers
             SET full_name = :full_name
             WHERE customer_id = :customer_id"
        );

        $stmt->execute([
            'full_name'   => $fullName,
            'customer_id' => $customerId
        ]);

        $message = 'Customer updated successfully.';
        $messageType = 'success';
    }
}


/* =========================
   DELETE CUSTOMER
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'delete') {

    $customerId = (int)($_POST['customer_id'] ?? 0);

    if ($customerId > 0) {

        /* Check for accounts */

        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM accounts
             WHERE customer_id = :customer_id"
        );

        $stmt->execute([
            'customer_id' => $customerId
        ]);

        $accountCount = (int)$stmt->fetchColumn();


        /* Check for transactions */

        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM transactions t
             JOIN accounts a
                ON a.account_id = t.account_id
             WHERE a.customer_id = :customer_id"
        );

        $stmt->execute([
            'customer_id' => $customerId
        ]);

        $transactionCount = (int)$stmt->fetchColumn();


        /* Check for alerts */

        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM alerts
             WHERE customer_id = :customer_id"
        );

        $stmt->execute([
            'customer_id' => $customerId
        ]);

        $alertCount = (int)$stmt->fetchColumn();


        if ($accountCount > 0 || $transactionCount > 0 || $alertCount > 0) {

            $message =
                "Customer cannot be deleted because they have "
                . $accountCount . " account(s), "
                . $transactionCount . " transaction(s), and "
                . $alertCount . " alert(s).";

            $messageType = 'warning';

        } else {

            $stmt = $pdo->prepare(
                "DELETE FROM customers
                 WHERE customer_id = :customer_id"
            );

            $stmt->execute([
                'customer_id' => $customerId
            ]);

            $message = 'Customer deleted successfully.';
            $messageType = 'success';
        }
    }
}


/* =========================
   GET CUSTOMERS
========================= */

$customers = $pdo->query(
    "SELECT
        customer_id,
        full_name,
        created_at
     FROM customers
     ORDER BY customer_id ASC"
)->fetchAll();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Management</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fa;
        }

        .page-header {
            background: #1f2937;
            color: white;
            padding: 24px 0;
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin-bottom: 5px;
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
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 21px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .table th {
            background: #f8fafc;
            font-size: 14px;
        }

        .table td {
            font-size: 14px;
            vertical-align: middle;
        }

        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .form-control {
            max-width: 500px;
        }

    </style>

</head>

<body>


<!-- HEADER -->

<div class="page-header">

    <div class="container">

        <h1>Customer Management</h1>

        <p>Add, edit, and manage monitored customers</p>

    </div>

</div>


<div class="container">


    <!-- NAVIGATION -->

    <div class="mb-3">

        <a
            href="analytics.php"
            class="btn btn-outline-secondary"
        >
            ← Back to Analytics
        </a>

        <a
            href="customers.php"
            class="btn btn-outline-primary"
        >
            Customer Risk List
        </a>

    </div>


    <!-- MESSAGE -->

    <?php if ($message !== ''): ?>

        <div class="alert alert-<?= $messageType ?>">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- ADD CUSTOMER -->

    <div class="content-card">

        <div class="section-title">

            Add New Customer

        </div>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="add"
            >

            <div class="mb-3">

                <label class="form-label">
                    Customer Name
                </label>

                <input
                    type="text"
                    name="full_name"
                    class="form-control"
                    placeholder="Example: Robert Johnson"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >
                + Add Customer
            </button>

        </form>

    </div>


    <!-- CUSTOMER LIST -->

    <div class="content-card">

        <div class="section-title">

            Existing Customers

        </div>


        <div class="table-responsive">

            <table class="table table-hover">

                <thead>

                    <tr>

                        <th>Customer ID</th>

                        <th>Customer Name</th>

                        <th>Created</th>

                        <th>Actions</th>

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


                            <td>

                                <?= htmlspecialchars(
                                    $customer['created_at']
                                ) ?>

                            </td>


                            <td>

                                <div class="action-buttons">


                                    <!-- VIEW -->

                                    <a
                                        href="customer_risk.php?customer_id=<?= (int)$customer['customer_id'] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        View
                                    </a>


                                    <!-- EDIT -->

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="edit"
                                        >

                                        <input
                                            type="hidden"
                                            name="customer_id"
                                            value="<?= (int)$customer['customer_id'] ?>"
                                        >

                                        <input
                                            type="text"
                                            name="full_name"
                                            value="<?= htmlspecialchars(
                                                $customer['full_name']
                                            ) ?>"
                                            class="form-control form-control-sm d-inline-block"
                                            style="width:180px;"
                                            required
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-success mt-1"
                                        >
                                            Save
                                        </button>

                                    </form>


                                    <!-- DELETE -->

                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm(
                                            'Are you sure you want to delete this customer?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="customer_id"
                                            value="<?= (int)$customer['customer_id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

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
