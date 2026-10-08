<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';

const LARGE_TXN_LIMIT = 10000;

$pdo = db();

$insert = $pdo->prepare(
    'INSERT INTO alerts (customer_id, txn_id, rule_code, severity, details)
     VALUES (:customer_id, :txn_id, :rule_code, :severity, :details)'
);

$checkDuplicate = $pdo->prepare(
    'SELECT COUNT(*)
     FROM alerts
     WHERE txn_id = :txn_id
       AND rule_code = :rule_code'
);

$stmt = $pdo->prepare(
    'SELECT t.txn_id, a.customer_id, t.amount, t.txn_type
     FROM transactions t
     JOIN accounts a ON a.account_id = t.account_id
     WHERE t.amount >= :limit'
);

$stmt->execute([
    'limit' => LARGE_TXN_LIMIT
]);

$largeCount = 0;

foreach ($stmt->fetchAll() as $row) {

    $severity = ((float)$row['amount'] >= 50000)
        ? 'HIGH'
        : 'MEDIUM';

    $checkDuplicate->execute([
        'txn_id'    => (int)$row['txn_id'],
        'rule_code' => 'LARGE_TXN'
    ]);

    $exists = (int)$checkDuplicate->fetchColumn();

    if ($exists === 0) {

        $insert->execute([
            'customer_id' => (int)$row['customer_id'],
            'txn_id'      => (int)$row['txn_id'],
            'rule_code'   => 'LARGE_TXN',
            'severity'    => $severity,
            'details'     => 'Transaction of $' . number_format($row['amount'], 2) . ' exceeded synthetic monitoring rule threshold'
        ]);

        $largeCount++;
    }
}

echo $largeCount;