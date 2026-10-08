<?php
require __DIR__ . '/config.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? '';
    $alertId   = (int)($_POST['alert_id'] ?? 0);
    if ($alertId > 0 && in_array($newStatus, ['OPEN', 'UNDER_REVIEW', 'ESCALATED', 'CLOSED'], true)) {
        $update = $pdo->prepare('UPDATE alerts SET status = :status WHERE alert_id = :id');
        $update->execute(['status' => $newStatus, 'id' => $alertId]);
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$alerts = $pdo->query(
    "SELECT al.alert_id, al.created_at, al.rule_code, al.severity, al.status, al.details, c.full_name
     FROM alerts al
     JOIN customers c ON c.customer_id = al.customer_id
     ORDER BY al.alert_id DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AML Alert Queue</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light container py-4">
    <h2>AML Compliance Dashboard</h2>
    <p class="text-muted">Transaction monitoring alerts for compliance review</p>
    
    <table class="table table-striped table-bordered mt-3">
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
                <td>#<?= htmlspecialchars($a['alert_id']) ?></td>
                <td><?= htmlspecialchars($a['full_name']) ?></td>
                <td><?= htmlspecialchars($a['rule_code']) ?></td>
                <td><span class="badge bg-<?= $a['severity'] === 'HIGH' ? 'danger' : 'warning' ?>"><?= htmlspecialchars($a['severity']) ?></span></td>
                <td><?= htmlspecialchars($a['details']) ?></td>
                <td><?= htmlspecialchars($a['status']) ?></td>
                <td>
                    <form method="post" class="d-flex gap-1">
                        <input type="hidden" name="alert_id" value="<?= $a['alert_id'] ?>">
                        <button class="btn btn-sm btn-primary" name="status" value="UNDER_REVIEW">Review</button>
                        <button class="btn btn-sm btn-danger" name="status" value="ESCALATED">Escalate</button>
                        <button class="btn btn-sm btn-secondary" name="status" value="CLOSED">Close</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
