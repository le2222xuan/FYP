<?php
session_start();
require_once __DIR__ . '/includes/paths.php';

$is_logged_in = isset($_SESSION['username']) || isset($_SESSION['user_username']);
if (!$is_logged_in || ($_SESSION['usertype'] ?? '') !== 'user') {
    header('Location: ' . base_url('login.php'));
    exit;
}

require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if ($user_id <= 0) {
    header('Location: ' . base_url('login.php'));
    exit;
}

$total_persons = 0;
$confirmed = 0;
$pending = 0;
$declined = 0;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$tier_filter = $_GET['tier'] ?? '';
$side_filter = $_GET['side'] ?? '';

$sql = "SELECT * FROM guests WHERE user_id = ?";
$params = [$user_id];

if ($search) {
    $sql .= " AND (guest_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $term = "%$search%";
    array_push($params, $term, $term, $term);
}
if ($status_filter) { $sql .= " AND invitation_status = ?"; $params[] = $status_filter; }
if ($tier_filter) { $sql .= " AND tier = ?"; $params[] = $tier_filter; }
if ($side_filter) { $sql .= " AND side = ?"; $params[] = $side_filter; }

$sql .= " ORDER BY guest_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$total_persons = 0;
$confirmed = 0;
$pending = 0;
$declined = 0;

foreach ($guests as $g) {
    $total_persons += $g['party_size'];
    if ($g['invitation_status'] === 'confirmed') $confirmed += $g['party_size'];
    elseif ($g['invitation_status'] === 'pending') $pending += $g['party_size'];
    elseif ($g['invitation_status'] === 'declined') $declined += $g['party_size'];
}

$active_page = 'guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Management</title>
    
    <!-- Font + Icon -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900&family=Bonheur+Royale&family=DM+Serif+Text:ital@0;1&family=Nanum+Myeongjo:wght@800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Corinthia:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- CSS  -->    
    <link rel="stylesheet" href="<?php echo base_url('assets/css/main-page.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/profile-edit.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/modal.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/myGuest.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/mySidebar.css'); ?>">

</head>

<body>

<div id="globalMessage" style="display:none; position:fixed; top:80px; right:20px; z-index:9999; padding:12px 20px; border-left:4px solid; border-radius:8px; font-size:14px; box-shadow:0 4px 12px rgba(0,0,0,0.1);"></div>

<?php include __DIR__ . '/includes/header_user.php'; ?>

<div class="app-wrapper">
    
    <!-- Sidebar -->
    <aside class="app-sidebar">
        <?php include __DIR__ . '/includes/mySidebar.php'; ?>
    </aside>
    
    <!-- Main Content -->
    <main class="app-main">
        <div class="content-card">
            
            <div class="page-header">
                <h1>Guests</h1>
                <div class="page-header-actions">
                    <form id="importForm" action="guest_actions.php" method="POST" enctype="multipart/form-data" style="display: none;">
                        <input type="file" id="importFile" name="import_file" accept=".csv">
                        <input type="hidden" name="action" value="import_csv">
                    </form>

                    <button type="button" class="btn" id="importBtn">
                        <i class="fas fa-upload"></i> Import CSV
                    </button>
                    <a href="guest_actions.php?export=csv" class="btn" id="exportBtn">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                    <button type="button" class="btn-dark" id="addGuestBtn">
                        <i class="fas fa-plus"></i> Add Guest
                    </button>
                    <button type="button" class="btn" id="bulkAddBtn">
                        <i class="fas fa-layer-group"></i> Bulk Add
                    </button>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number" id="totalPersons"><?= $total_persons ?></span>
                    <span class="stat-label">Total Invited</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="confirmedCount"><?= $confirmed ?></span>
                    <span class="stat-label">Confirmed</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="pendingCount"><?= $pending ?></span>
                    <span class="stat-label">Pending</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="declinedCount"><?= $declined ?></span>
                    <span class="stat-label">Declined</span>
                </div>
            </div>
            
            <!-- Filter bar -->
            <div class="filter-bar">
                <form method="GET" action="" class="filter-form">
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search guests..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    
                    <select name="tier" class="filter-select">
                        <option value="">All Tiers</option>
                        <option value="A" <?= ($_GET['tier']??'')=='A'?'selected':'' ?>>A</option>
                        <option value="B" <?= ($_GET['tier']??'')=='B'?'selected':'' ?>>B</option>
                        <option value="C" <?= ($_GET['tier']??'')=='C'?'selected':'' ?>>C</option>
                        <option value="D" <?= ($_GET['tier']??'')=='D'?'selected':'' ?>>D</option>
                    </select>
                    
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="pending" <?= ($_GET['status']??'')=='pending'?'selected':'' ?>>Pending</option>
                        <option value="confirmed" <?= ($_GET['status']??'')=='confirmed'?'selected':'' ?>>Confirmed</option>
                        <option value="declined" <?= ($_GET['status']??'')=='declined'?'selected':'' ?>>Declined</option>
                    </select>
                    
                    <select name="side" class="filter-select">
                        <option value="">All Sides</option>
                        <option value="bride" <?= ($_GET['side']??'')=='bride'?'selected':'' ?>>Bride's Side</option>
                        <option value="groom" <?= ($_GET['side']??'')=='groom'?'selected':'' ?>>Groom's Side</option>
                        <option value="mutual" <?= ($_GET['side']??'')=='mutual'?'selected':'' ?>>Mutual</option>
                    </select>
                    
                    <button type="submit" class="btn-dark" style="padding: 10px 20px;">Apply</button>
                    <a href="?" class="btn">Reset</a>
                </form>
            </div>

            <div class="bulk-bar" id="bulkBar" style="display:none;">
                <div class="bulk-label">
                    <span id="selectedCount">0</span> selected
                </div>
                <div class="bulk-actions">
                    <select id="bulkTierSelect" class="bulk-select">
                        <option value="">Set Tier</option>
                        <option value="A">Tier A</option>
                        <option value="B">Tier B</option>
                        <option value="C">Tier C</option>
                        <option value="D">Tier D</option>
                    </select>
                    <select id="bulkStatusSelect" class="bulk-select">
                        <option value="">Set Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="declined">Declined</option>
                    </select>
                    <button type="button" class="bulk-btn" id="bulkUpdateBtn">Apply Changes</button>
                    <button type="button" class="bulk-btn" id="bulkDeleteBtn">Delete Selected</button>
                </div>
            </div>
            
            <!-- Guest List -->
            <div style="overflow-x: auto;">
                <table class="guest-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="selectAllCheckbox"></th>
                            <th>Name</th>
                            <th>Party</th>
                            <th>Side</th>
                            <th>Table</th>
                            <th>Tier</th>
                            <th>Status</th>
                            <th>Phone</th>
                            <th style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="guestTableBody">
                        <?php foreach ($guests as $guest): ?>
                        <tr
                            data-id="<?= $guest['guest_id'] ?>"
                            data-guest-id="<?= $guest['guest_id'] ?>"
                            data-guest-name="<?= htmlspecialchars($guest['guest_name'], ENT_QUOTES) ?>"
                            data-side="<?= htmlspecialchars($guest['side'] ?? 'mutual', ENT_QUOTES) ?>"
                            data-party-size="<?= (int) $guest['party_size'] ?>"
                            data-table-number="<?= htmlspecialchars($guest['table_number'] ?? '', ENT_QUOTES) ?>"
                            data-invitation-status="<?= htmlspecialchars($guest['invitation_status'] ?? 'pending', ENT_QUOTES) ?>"
                            data-tier="<?= htmlspecialchars($guest['tier'] ?? '', ENT_QUOTES) ?>"
                            data-email="<?= htmlspecialchars($guest['email'] ?? '', ENT_QUOTES) ?>"
                            data-phone="<?= htmlspecialchars($guest['phone'] ?? '', ENT_QUOTES) ?>"
                            data-address="<?= htmlspecialchars($guest['address'] ?? '', ENT_QUOTES) ?>"
                            data-dietary-notes="<?= htmlspecialchars($guest['dietary_notes'] ?? '', ENT_QUOTES) ?>"
                            data-notes="<?= htmlspecialchars($guest['notes'] ?? '', ENT_QUOTES) ?>"
                        >
                            <td><input type="checkbox" class="row-checkbox" value="<?= $guest['guest_id'] ?>"></td>
                            <td><?= htmlspecialchars($guest['guest_name']) ?></td>
                            <td><?= (int)$guest['party_size'] ?></td>
                            <td><?= ucfirst(htmlspecialchars($guest['side'] ?? 'mutual')) ?></td>
                            <td><?= htmlspecialchars($guest['table_number'] ?: '—') ?></td>
                            <td>
                                <select class="tier-dropdown" data-guest-id="<?= $guest['guest_id'] ?>" style="padding: 4px 8px; border-radius: 4px; border: 1px solid #e0e0e0;">
                                    <option value="A" <?= ($guest['tier'] ?? '') == 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= ($guest['tier'] ?? '') == 'B' ? 'selected' : '' ?>>B</option>
                                    <option value="C" <?= ($guest['tier'] ?? '') == 'C' ? 'selected' : '' ?>>C</option>
                                </select>
                            </td>
                            <td>
                                <select class="status-select" data-guest-id="<?= $guest['guest_id'] ?>" style="padding: 4px 8px; border-radius: 4px; border: 1px solid #e0e0e0;">
                                    <option value="pending" <?= $guest['invitation_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $guest['invitation_status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="declined" <?= $guest['invitation_status'] == 'declined' ? 'selected' : '' ?>>Declined</option>
                                </select>
                            </td>
                            <td><?= htmlspecialchars($guest['phone'] ?: '—') ?></td>
                            <td>
                                <div class="table-actions">
                                    <i class="far fa-edit edit-icon" data-id="<?= $guest['guest_id'] ?>" title="Edit"></i>
                                    <i class="far fa-trash-alt delete-icon" data-id="<?= $guest['guest_id'] ?>" title="Delete"></i>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($guests)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #968a7e;">
                                No guests yet. Click "Add Guest" to get started.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </main>
</div>

<!-- Edit modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modalTitle">Edit Guest</h3>
            <button class="modal-close" id="modalCloseBtn">&times;</button>
        </div>
        <input type="hidden" id="modalGuestId" value="">
        
        <div class="modal-field">
            <label>Full Name *</label>
            <input type="text" id="modalName" >
        </div>
        
        <div class="modal-row">
            <div class="modal-field">
                <label>Party Size</label>
                <input type="number" id="modalParty" value="1" min="1">
            </div>
            <div class="modal-field">
                <label>Side</label>
                <select id="modalSide">
                    <option value="mutual">Mutual</option>
                    <option value="bride">Bride's Side</option>
                    <option value="groom">Groom's Side</option>
                </select>
            </div>
        </div>
        
        <div class="modal-row">
            <div class="modal-field">
                <label>Table / Location</label>
                <input type="text" id="modalTable" >
            </div>
            <div class="modal-field">
                <label>Invitation Status</label>
                <select id="modalStatus">
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="declined">Declined</option>
                </select>
            </div>
        </div>
        
        <div class="modal-row">
            <div class="modal-field">
                <label>Email</label>
                <input type="email" id="modalEmail" >
            </div>
            <div class="modal-field">
                <label>Phone</label>
                <input type="tel" id="modalPhone">
            </div>
        </div>
        
        <div class="modal-field">
            <label>Address</label>
            <textarea id="modalAddress" rows="2"></textarea>
        </div>
        
            <div class="modal-field">
                <label>Tier</label>
                <select id="modalTier">
                    <option value="">  </option>
                    <option value="A">Tier A</option>
                    <option value="B">Tier B</option>
                    <option value="C">Tier C</option>
                </select>
            </div>

        <div class="modal-field">
            <label>Dietary Notes</label>
            <textarea id="modalDietaryNotes" rows="2" placeholder="Allergies, vegetarian, etc."></textarea>
        </div>
        
        <div class="modal-field">
            <label>General Notes</label>
            <textarea id="modalNotes" rows="2" placeholder="Any special requirements..."></textarea>
        </div>
        
        <div class="modal-actions">
            <button class="modal-delete" id="modalDeleteBtn" style="margin-right:auto; padding:8px 20px; background:#fff; color:#e74c3c; border:1px solid #e74c3c; border-radius:8px; cursor:pointer; display:none;">Delete</button>
            <button class="modal-cancel" id="modalCancelBtn">Cancel</button>
            <button class="modal-save" id="modalSaveBtn">Save Changes</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="bulkAddModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Bulk Add Guests</h3>
            <button class="modal-close" id="bulkAddCloseBtn">&times;</button>
        </div>

        <div class="modal-field">
            <label>Guest list *</label>
            <textarea id="bulkAddList" rows="8" placeholder="One guest per line. Format: Name, Party Size (optional), Side (optional), Status (optional)"></textarea>
        </div>

        <div class="modal-row">
            <div class="modal-field">
                <label>Default Party Size</label>
                <input type="number" id="bulkAddDefaultParty" value="1" min="1">
            </div>
            <div class="modal-field">
                <label>Default Side</label>
                <select id="bulkAddDefaultSide">
                    <option value="mutual">Mutual</option>
                    <option value="bride">Bride's Side</option>
                    <option value="groom">Groom's Side</option>
                </select>
            </div>
        </div>

        <div class="modal-field">
            <label>Default Invitation Status</label>
            <select id="bulkAddDefaultStatus">
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="declined">Declined</option>
            </select>
        </div>

        <div class="modal-actions">
            <button class="modal-cancel" id="bulkAddCancelBtn">Cancel</button>
            <button class="modal-save" id="bulkAddSaveBtn">Add Guests</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="assets/js/myGuest.js?v=<?= filemtime(__DIR__ . '/assets/js/myGuest.js') ?>"></script>