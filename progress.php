<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['usertype'] ?? '') !== 'user')) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$active_page = 'overview';

$orderSql = "SELECT o.order_id, o.wedding_date, o.status, h.hall_name
    FROM orders o
    LEFT JOIN halls h ON h.hall_id = o.hall_id
    WHERE o.user_id = ? AND o.is_deleted = 0
    ORDER BY o.order_id DESC
    LIMIT 1";
$orderStmt = $conn->prepare($orderSql);
$orderStmt->bind_param('i', $userId);
$orderStmt->execute();
$latestOrder = $orderStmt->get_result()->fetch_assoc() ?: null;

$progressItems = [];
if ($latestOrder) {
    $progressSql = "SELECT step_number, task_name, task_category, task_status, due_date, completed_at, admin_remark
        FROM order_progress
        WHERE order_id = ?
        ORDER BY COALESCE(step_number, 999), progress_id ASC";
    $progressStmt = $conn->prepare($progressSql);
    $progressStmt->bind_param('i', $latestOrder['order_id']);
    $progressStmt->execute();
    $progressItems = $progressStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$completedCount = 0;
foreach ($progressItems as $item) {
    if (($item['task_status'] ?? '') === 'completed') {
        $completedCount++;
    }
}

$totalCount = count($progressItems);
$progressPercent = $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Progress</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/mySidebar.css'); ?>">
    <style>
        .progress-page {
            max-width: 1280px;
            margin: 0 auto;
            background: #fff;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 24px 60px -50px rgba(0, 0, 0, 0.35);
        }

        .progress-page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            margin-bottom: 24px;
        }

        .progress-page-card {
            background: #fcfaf7;
            padding: 1.5rem;
            border: 1px solid #e5dfd7;
            border-radius: 20px;
        }

        .progress-page h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem;
            margin-bottom: 0.75rem;
            color: #1f1e1c;
        }

        .progress-page p {
            color: #8f7f6f;
        }

        .progress-page-card a {
            color: #1f1e1c;
            text-decoration: underline;
        }

        .progress-overview {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .progress-overview .metric {
            background: #fff;
            border: 1px solid #eadfd4;
            border-radius: 18px;
            padding: 18px;
        }

        .metric-label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: #8f7f6f;
            margin-bottom: 8px;
        }

        .metric strong {
            font-size: 1.15rem;
            color: #1f1e1c;
        }

        .progress-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 14px;
        }

        .progress-item {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 16px;
            align-items: center;
            background: #fff;
            border: 1px solid #ece3da;
            border-radius: 18px;
            padding: 18px;
        }

        .progress-step {
            font-size: 0.78rem;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            color: #8f7f6f;
        }

        .progress-task {
            font-weight: 600;
            color: #1f1e1c;
        }

        .progress-meta {
            display: block;
            margin-top: 6px;
            color: #7e7368;
            font-size: 0.9rem;
        }

        .progress-status {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 992px) {
            .progress-overview {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .progress-page {
                padding: 20px;
            }

            .progress-page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .progress-overview {
                grid-template-columns: 1fr;
            }

            .progress-item {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/header_user.php'; ?>

<div class="app-wrapper">
    <aside class="app-sidebar">
        <?php include __DIR__ . '/includes/mySidebar.php'; ?>
    </aside>

    <main class="app-main">
        <div class="progress-page">
            <div class="progress-page-header">
                <div>
                    <h1>Service Progress</h1>
                    <p>Track the current execution status of your wedding order.</p>
                </div>
                <a href="myOverview.php">Back to Overview</a>
            </div>

            <?php if (!$latestOrder): ?>
            <div class="progress-page-card">
                <p>No order found yet. Once you place an order, your progress tasks will appear here.</p>
            </div>
            <?php else: ?>
            <div class="progress-overview">
                <div class="metric">
                    <span class="metric-label">Order</span>
                    <strong>#<?php echo (int) $latestOrder['order_id']; ?></strong>
                </div>
                <div class="metric">
                    <span class="metric-label">Venue</span>
                    <strong><?php echo !empty($latestOrder['hall_name']) ? htmlspecialchars($latestOrder['hall_name']) : 'Pending'; ?></strong>
                </div>
                <div class="metric">
                    <span class="metric-label">Wedding Date</span>
                    <strong><?php echo !empty($latestOrder['wedding_date']) ? date('d M Y', strtotime($latestOrder['wedding_date'])) : 'Pending'; ?></strong>
                </div>
                <div class="metric">
                    <span class="metric-label">Completion</span>
                    <strong><?php echo $progressPercent; ?>%</strong>
                </div>
            </div>

            <div class="progress-page-card">
                <?php if (empty($progressItems)): ?>
                <p>No service tasks created yet for this order.</p>
                <?php else: ?>
                <ul class="progress-list">
                    <?php foreach ($progressItems as $item): ?>
                    <li class="progress-item">
                        <div class="progress-step">
                            <?php echo !empty($item['step_number']) ? 'Step ' . (int) $item['step_number'] : 'Task'; ?>
                        </div>
                        <div>
                            <div class="progress-task"><?php echo htmlspecialchars((string) ($item['task_name'] ?? 'Untitled task')); ?></div>
                            <span class="progress-meta">
                                <?php echo ucfirst(htmlspecialchars((string) ($item['task_category'] ?? 'other'))); ?>
                                <?php if (!empty($item['due_date'])): ?>
                                · Due <?php echo date('d M Y', strtotime($item['due_date'])); ?>
                                <?php endif; ?>
                                <?php if (!empty($item['admin_remark'])): ?>
                                · <?php echo htmlspecialchars((string) $item['admin_remark']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="progress-status status-<?php echo strtolower(htmlspecialchars((string) ($item['task_status'] ?? 'pending'))); ?>">
                            <?php echo htmlspecialchars((string) ($item['task_status'] ?? 'pending')); ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
