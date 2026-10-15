<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config_test.php';

const LARGE_TXN_LIMIT = 10000;
const STRUCTURING_MIN = 3000;
const STRUCTURING_MAX = 9900;
const STRUCTURING_COUNT = 5;

$pdo = db();

$insert = $pdo->prepare(
    'INSERT INTO alerts
        (customer_id, txn_id, rule_code, severity, details)
     VALUES
        (:customer_id, :txn_id, :rule_code, :severity, :details)
     ON CONFLICT (txn_id, rule_code) DO NOTHING'
);

/* Rule 1: Large transactions */

$largeQuery = $pdo->prepare(
    'SELECT t.txn_id, a.customer_id, t.amount
     FROM transactions t
     JOIN accounts a ON a.account_id = t.account_id
     WHERE t.amount >= :limit'
);

$largeQuery->execute(['limit' => LARGE_TXN_LIMIT]);

$largeCount = 0;

foreach ($largeQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $amount = (float) $row['amount'];

    $severity = ($amount >= 50000) ? 'HIGH' : 'MEDIUM';

    $insert->execute([
        'customer_id' => (int) $row['customer_id'],
        'txn_id'      => (int) $row['txn_id'],
        'rule_code'   => 'LARGE_TXN',
        'severity'    => $severity,
        'details'     => 'Transaction of $'
            . number_format($amount, 2)
            . ' exceeded synthetic monitoring rule threshold'
    ]);

    $largeCount += $insert->rowCount();
}

/* Rule 2: Five qualifying transactions in a rolling two-hour window */

$structuringQuery = $pdo->prepare(
    "WITH qualifying_transactions AS (
        SELECT
            t.txn_id,
            a.customer_id,
            t.amount,
            t.txn_time
        FROM transactions t
        JOIN accounts a ON a.account_id = t.account_id
        WHERE t.amount BETWEEN :minimum AND :maximum
    )
    SELECT
        current_txn.txn_id,
        current_txn.customer_id,
        current_txn.amount,
        current_txn.txn_time,
        COUNT(prior_txn.txn_id) AS transaction_count
    FROM qualifying_transactions current_txn
    JOIN qualifying_transactions prior_txn
      ON prior_txn.customer_id = current_txn.customer_id
     AND prior_txn.txn_time BETWEEN
         current_txn.txn_time - INTERVAL '2 hours'
         AND current_txn.txn_time
    GROUP BY
        current_txn.txn_id,
        current_txn.customer_id,
        current_txn.amount,
        current_txn.txn_time
    HAVING COUNT(prior_txn.txn_id) >= :minimum_count
    ORDER BY current_txn.txn_time, current_txn.txn_id"
);

$structuringQuery->execute([
    'minimum'       => STRUCTURING_MIN,
    'maximum'       => STRUCTURING_MAX,
    'minimum_count' => STRUCTURING_COUNT
]);

$structuringCount = 0;

foreach (
    $structuringQuery->fetchAll(PDO::FETCH_ASSOC) as $row
) {
    $insert->execute([
        'customer_id' => (int) $row['customer_id'],
        'txn_id'      => (int) $row['txn_id'],
        'rule_code'   => 'STRUCTURING',
        'severity'    => 'HIGH',
        'details'     => 'Customer had at least '
            . STRUCTURING_COUNT
            . ' qualifying transactions within a rolling two-hour window'
    ]);

    $structuringCount += $insert->rowCount();
}

echo "LARGE_TXN alerts created: {$largeCount}" . PHP_EOL;
echo "STRUCTURING alerts created: {$structuringCount}" . PHP_EOL;