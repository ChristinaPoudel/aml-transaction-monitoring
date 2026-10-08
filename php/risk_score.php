<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/config.php';

$pdo = db();

$rows = $pdo->query(
    "SELECT c.customer_id,
            COALESCE(v.txns_7d, 0) AS txns_7d,
            COALESCE(v.amount_7d, 0) AS amount_7d,
            COALESCE(m.risky_weight_30d, 0) AS risky_weight_30d
     FROM customers c
     LEFT JOIN (
         SELECT a.customer_id,
                COUNT(*) AS txns_7d,
                SUM(t.amount) AS amount_7d
         FROM transactions t
         JOIN accounts a ON a.account_id = t.account_id
         WHERE t.txn_time >= NOW() - INTERVAL '7 days'
         GROUP BY a.customer_id
     ) v ON v.customer_id = c.customer_id
     LEFT JOIN (
         SELECT a.customer_id,
                SUM(mt.risk_weight) AS risky_weight_30d
         FROM transactions t
         JOIN accounts a ON a.account_id = t.account_id
         JOIN merchant_types mt ON mt.merchant_type_id = t.merchant_type_id
         WHERE mt.risk_weight >= 6
           AND t.txn_time >= NOW() - INTERVAL '30 days'
         GROUP BY a.customer_id
     ) m ON m.customer_id = c.customer_id"
)->fetchAll();

$save = $pdo->prepare(
    'INSERT INTO risk_scores
        (customer_id, score_date, velocity_score, mcc_score, total_score, risk_band)
     VALUES
        (:customer_id, CURRENT_DATE, :velocity, :mcc, :total, :band)
     ON CONFLICT (customer_id, score_date)
     DO UPDATE SET
        velocity_score = EXCLUDED.velocity_score,
        mcc_score = EXCLUDED.mcc_score,
        total_score = EXCLUDED.total_score,
        risk_band = EXCLUDED.risk_band'
);

foreach ($rows as $r) {

    $velocity = min(
        60,
        (int)$r['txns_7d'] * 2 +
        (int)floor((float)$r['amount_7d'] / 1000)
    );

    $mcc = min(
        40,
        (int)$r['risky_weight_30d']
    );

    $total = $velocity + $mcc;

    $band = $total >= 60
        ? 'HIGH'
        : ($total >= 45 ? 'MEDIUM' : 'LOW');

    $save->execute([
        'customer_id' => $r['customer_id'],
        'velocity'    => $velocity,
        'mcc'         => $mcc,
        'total'       => $total,
        'band'        => $band
    ]);
}

echo "Risk scoring finished successfully.\n";
