<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['usertype'] ?? '') !== 'user')) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$active_page = 'overview';

function fetchUserProfile(mysqli $conn, int $user_id): array
{
    $sql = "SELECT up.*, l.first_name, l.last_name, l.username
            FROM user_profiles up
            INNER JOIN login l ON l.id = up.user_id
            WHERE up.user_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ?: [];
}

function fetchLatestOrder(mysqli $conn, int $user_id): ?array
{
    $sql = "SELECT o.*, h.hall_name
            FROM orders o
            LEFT JOIN halls h ON h.hall_id = o.hall_id
            WHERE o.user_id = ? AND o.is_deleted = 0
            ORDER BY o.order_id DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ?: null;
}

function fetchPaymentStatuses(mysqli $conn, int $order_id): array
{
    $sql = "SELECT payment_type, payment_status
            FROM payments
            WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();

    $statuses = [
        'deposit' => false,
        'midterm' => false,
        'final' => false,
    ];

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $paymentType = (string) ($row['payment_type'] ?? '');
        $isSuccessful = (string) ($row['payment_status'] ?? '') === 'success';

        if (!$isSuccessful) {
            continue;
        }

        if ($paymentType === 'full') {
            $statuses['deposit'] = true;
            $statuses['midterm'] = true;
            $statuses['final'] = true;
            break;
        }

        if (array_key_exists($paymentType, $statuses)) {
            $statuses[$paymentType] = true;
        }
    }

    return $statuses;
}

function fetchGuestStats(mysqli $conn, int $user_id): array
{
    $sql = "SELECT
                COALESCE(SUM(party_size), 0) AS total_invited,
                COALESCE(SUM(CASE WHEN invitation_status = 'confirmed' THEN party_size ELSE 0 END), 0) AS confirmed,
                COALESCE(SUM(CASE WHEN invitation_status = 'pending' THEN party_size ELSE 0 END), 0) AS pending,
                COALESCE(COUNT(CASE WHEN dietary_notes IS NOT NULL AND dietary_notes <> '' THEN 1 END), 0) AS dietary_noted
            FROM guests
            WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ?: [
        'total_invited' => 0,
        'confirmed' => 0,
        'pending' => 0,
        'dietary_noted' => 0,
    ];
}

function fetchChecklistTasks(mysqli $conn, int $user_id): array
{
    $sql = "SELECT checklist_id, task_text, task_date, task_time, status, created_at
            FROM checklist
            WHERE user_id = ?
            ORDER BY status ASC, task_date ASC, task_time ASC, created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetchOrderProgress(mysqli $conn, int $order_id): array
{
    $sql = "SELECT task_name, task_status, step_number, due_date, completed_at
            FROM order_progress
            WHERE order_id = ?
            ORDER BY COALESCE(step_number, 999), progress_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function daysUntil(?string $date): ?int
{
    if (!$date) {
        return null;
    }

    $today = new DateTime('today');
    $target = new DateTime($date);
    $diff = $today->diff($target);
    return $target < $today ? -$diff->days : $diff->days;
}

function buildCoupleName(array $profile): string
{
    $userFirst = trim((string) ($profile['first_name'] ?? ''));
    $userLast = trim((string) ($profile['last_name'] ?? ''));
    $partnerFirst = trim((string) ($profile['partner_first_name'] ?? ''));
    $partnerLast = trim((string) ($profile['partner_last_name'] ?? ''));

    if ($userFirst !== '' && $partnerFirst !== '') {
        return $userFirst . ' & ' . $partnerFirst;
    }

    if ($userFirst !== '') {
        return trim($userFirst . ' ' . $userLast);
    }

    return (string) ($profile['username'] ?? 'Our Wedding');
}

$profile = fetchUserProfile($conn, $user_id);
$order = fetchLatestOrder($conn, $user_id);
$guestStats = fetchGuestStats($conn, $user_id);
$tasks = fetchChecklistTasks($conn, $user_id);

$display_mode = $order ? 'has_order' : 'no_order';

$orderProgressItems = $order ? fetchOrderProgress($conn, (int) $order['order_id']) : [];
$orderProgressTotal = count($orderProgressItems);
$orderProgressCompleted = 0;
foreach ($orderProgressItems as $item) {
    if (($item['task_status'] ?? '') === 'completed') {
        $orderProgressCompleted++;
    }
}

$servicePercent = $orderProgressTotal > 0 ? (int) round(($orderProgressCompleted / $orderProgressTotal) * 100) : 0;
$serviceSteps = ['PLANNING', 'PREPARATION', 'EXECUTION', 'COMPLETE'];
$serviceCurrentIndex = 0;
if ($servicePercent >= 75) {
    $serviceCurrentIndex = 3;
} elseif ($servicePercent >= 50) {
    $serviceCurrentIndex = 2;
} elseif ($servicePercent >= 25) {
    $serviceCurrentIndex = 1;
}

$paymentStatuses = $order ? fetchPaymentStatuses($conn, (int) $order['order_id']) : [
    'deposit' => false,
    'midterm' => false,
    'final' => false,
];

$depositPaid = $paymentStatuses['deposit'];
$midtermPaid = $paymentStatuses['midterm'];
$finalPaid = $paymentStatuses['final'];

$paymentSteps = [
    ['key' => 'deposit', 'label' => 'DEPOSIT', 'amount' => (float) ($order['deposit_amount'] ?? 0), 'paid' => $depositPaid],
    ['key' => 'midterm', 'label' => 'MID-TERM', 'amount' => (float) ($order['midterm_amount'] ?? 0), 'paid' => $midtermPaid],
    ['key' => 'final', 'label' => 'FINAL', 'amount' => (float) ($order['final_amount'] ?? 0), 'paid' => $finalPaid],
];

$paymentCurrentIndex = 0;
foreach ($paymentSteps as $idx => $step) {
    if (!$step['paid']) {
        $paymentCurrentIndex = $idx;
        break;
    }

    $paymentCurrentIndex = $idx;
}

$weddingDate = $order['wedding_date'] ?? ($profile['wedding_date'] ?? null);
$daysLeft = daysUntil($weddingDate);
$coupleName = buildCoupleName($profile);
$userDisplayName = trim((string) ($profile['first_name'] ?? '') . ' ' . (string) ($profile['last_name'] ?? ''));
if ($userDisplayName === '') {
    $userDisplayName = (string) ($profile['username'] ?? ($_SESSION['username'] ?? 'User'));
}
$guestCount = (int) ($order['guest_count'] ?? 0);
if ($guestCount <= 0) {
    $guestCount = (int) ($guestStats['total_invited'] ?? 0);
}

$paymentNextNote = 'Please complete your order to unlock payment timeline';
if ($display_mode === 'has_order') {
    if (!$depositPaid) {
        $paymentNextNote = 'Start with deposit payment';
    } elseif (!$midtermPaid) {
        $paymentNextNote = 'Mid-term payment due soon';
    } elseif (!$finalPaid) {
        $paymentNextNote = 'Final payment is pending';
    } else {
        $paymentNextNote = 'All payments completed';
    }
}

$serviceNextNote = 'No progress data yet';
if ($display_mode === 'has_order') {
    if ($orderProgressTotal === 0) {
        $serviceNextNote = 'No service tasks created yet';
    } else {
        $serviceNextNote = $orderProgressCompleted . '/' . $orderProgressTotal . ' tasks completed';
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Overview</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/mySidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/myOverview.css'); ?>">
</head>

<body>
    <?php include __DIR__ . '/includes/header_user.php'; ?>

    <div class="app-wrapper">
        <aside class="app-sidebar">
            <?php include __DIR__ . '/includes/mySidebar.php'; ?>
        </aside>

        <main class="app-main">
            <div class="content-card overview-page">
                <section class="hero-integrated">
                    <a href="profile_edit.php" class="hero-edit-profile" aria-label="Edit profile" title="Edit profile">
                        <i class="fas fa-pen"></i>
                    </a>
                    <div class="hero-overlay-luxe">
                        <div class="title-block">
                            <div class="hero-overline">The wedding of</div>
                            <div class="hero-names"><?php echo htmlspecialchars($coupleName); ?></div>
                            <a href="profile_edit.php" class="view-all-link">VIEW DETAILS</a>
                        </div>
                        <div class="countdown-block">
                            <div class="countdown-label">Days to go</div>
                            <span
                                class="countdown-digits"><?php echo ($daysLeft !== null && $daysLeft >= 0) ? $daysLeft : '0'; ?></span>
                            <span class="countdown-unit">days</span>
                        </div>
                    </div>
                </section>

                <section class="info-card-grid">
                    <div class="info-card">
                        <div class="label">Wedding Date</div>
                        <div class="value"><?php echo $weddingDate ? date('Y.m.d', strtotime($weddingDate)) : '-'; ?>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="label">Venue</div>
                        <div class="value">
                            <?php echo !empty($order['hall_name']) ? htmlspecialchars($order['hall_name']) : 'No venue selected'; ?>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="label">Guest Count</div>
                        <div class="value"><?php echo $guestCount > 0 ? number_format($guestCount) : '-'; ?></div>
                    </div>
                </section>

                <section class="progress-panel">
                    <div class="progress-header">
                        <h3>Wedding Progress</h3>
                        <a href="progress.php" class="view-all-link">VIEW DETAILS</a>
                    </div>

                    <?php
                    if ($display_mode == 'no_order') {
                        echo "<p class='empty-state' style='color: #908e8a; font-size: 18px; font-weight: 200; text-align: center; padding: 20px; '>
                            No orders yet. Place your first order to get started.
                        </p>";
                    }
                    ?>

                    <?php if ($display_mode === 'has_order'): ?>
                        <div class="timeline-container">
                            <div class="timeline">
                                <div class="timeline-head">
                                    <div class="timeline-title"><i class="fas fa-credit-card"></i> PAYMENT TIMELINE</div>
                                    <a class="timeline-link-btn" href="order_history.php" title="Go to Orders"><i
                                            class="fas fa-arrow-right"></i></a>
                                </div>
                                <div class="stepper-wrapper">
                                    <div class="stepper-line"></div>
                                    <?php foreach ($paymentSteps as $idx => $step): ?>
                                        <?php
                                        $stepClass = '';
                                        if ($step['paid']) {
                                            $stepClass = 'completed';
                                        } elseif ($idx === $paymentCurrentIndex) {
                                            $stepClass = 'current';
                                        }
                                        ?>
                                        <div class="step <?php echo $stepClass; ?>">
                                            <div class="step-icon">
                                                <?php if ($step['paid'])
                                                    echo '<i class="fas fa-check"></i>'; ?></div>
                                            <div class="step-label"><?php echo $step['label']; ?></div>
                                            <div class="step-amount">¥<?php echo number_format($step['amount'], 0); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="timeline-note"><?php echo htmlspecialchars($paymentNextNote); ?></div>
                            </div>

                            <div class="timeline">
                                <div class="timeline-head">
                                    <div class="timeline-title"><i class="fas fa-clipboard-list"></i> SERVICE PROGRESS</div>
                                    <a class="timeline-link-btn" href="progress.php" title="Go to Progress"><i
                                            class="fas fa-arrow-right"></i></a>
                                </div>
                                <div class="stepper-wrapper four-steps">
                                    <div class="stepper-line"></div>
                                    <?php foreach ($serviceSteps as $idx => $label): ?>
                                        <?php
                                        $stepClass = '';
                                        if ($idx < $serviceCurrentIndex || ($servicePercent === 100 && $idx <= $serviceCurrentIndex)) {
                                            $stepClass = 'completed';
                                        } elseif ($idx === $serviceCurrentIndex) {
                                            $stepClass = 'current';
                                        }
                                        ?>
                                        <div class="step <?php echo $stepClass; ?>">
                                            <div class="step-icon">
                                                <?php if ($stepClass === 'completed')
                                                    echo '<i class="fas fa-check"></i>'; ?>
                                            </div>
                                            <div class="step-label"><?php echo $label; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="timeline-note"><?php echo htmlspecialchars($serviceNextNote); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="planning-container">
                    <div class="tasks-panel">
                        <div class="progress-header ">
                            <h3>Checklist</h3>
                            <a href="mySchedule.php" class="add-task-btn" title="Open checklist"><i
                                    class="fas fa-plus"></i></a>
                        </div>

                        <?php if (!empty($tasks)): ?>
                            <ul class="task-list">
                                <?php foreach ($tasks as $task): ?>
                                    <li class="task-item <?php echo ((int) $task['status'] === 1) ? 'completed' : ''; ?>">
                                        <input type="checkbox" class="task-check" disabled <?php echo ((int) $task['status'] === 1) ? 'checked' : ''; ?>>
                                        <div class="task-content">
                                            <span class="task-text"><?php echo htmlspecialchars($task['task_text']); ?></span>
                                            <?php if (!empty($task['task_date'])): ?>
                                                <span
                                                    class="task-meta"><?php echo date('d M Y', strtotime($task['task_date'])); ?><?php echo !empty($task['task_time']) ? ' · ' . date('H:i', strtotime($task['task_time'])) : ''; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state" style="font-size: 18px;
                            font-weight: 200; 
                            line-height: 1.2em;
                            text-align: center;
                            color: #9b8874;
                            padding: 1.2rem;

                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            
                            min-height: 200px;
                            gap: 1.5rem;">No tasks yet.</div>
                        <?php endif; ?>
                    </div>

                    <div class="guest-overview-panel">
                        <div class="overview-title">Guest Overview</div>
                        <div class="stat-row"><span>Total
                                Invited</span><strong><?php echo number_format((int) $guestStats['total_invited']); ?></strong>
                        </div>
                        <div class="stat-row">
                            <span>Confirmed</span><strong><?php echo number_format((int) $guestStats['confirmed']); ?></strong>
                        </div>
                        <div class="stat-row">
                            <span>Pending</span><strong><?php echo number_format((int) $guestStats['pending']); ?></strong>
                        </div>
                        <div class="stat-row"><span>Dietary
                                Noted</span><strong><?php echo number_format((int) $guestStats['dietary_noted']); ?></strong>
                        </div>
                        <button class="btn-manage" onclick="window.location.href='myGuest.php'">Manage Guests</button>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>

</html>