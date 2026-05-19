<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('view', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');
$canCreate = can('create');
$canUpdate = can('update');
$canDelete = can('delete');

$msg = (string)($_GET['msg'] ?? '');
$collid = (int)($_GET['collid'] ?? 0); // 0 = all
$deptid = (int)($_GET['deptid'] ?? 0); // 0 = all
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$total = 0;
$totalPages = 1;
$offset = 0;

$schools = [];
$departments = [];
$programs = [];
$titleParts = [];

try {
    $schools = $pdo->query("SELECT * FROM colleges WHERE collid <> 0 ORDER BY collfullname")->fetchAll();
} catch (Throwable $e) { $schools = []; }

try {
    $departments = $pdo->query("
        SELECT d.*, c.collshortname
        FROM departments d
        LEFT JOIN colleges c ON c.collid = d.deptcollid
        WHERE d.deptid <> 0
        ORDER BY d.deptfullname
    ")->fetchAll();
} catch (Throwable $e) { $departments = []; }

try {
    // If a department is chosen but no school yet, infer the school.
    if ($deptid > 0 && $collid === 0) {
        $stmt = $pdo->prepare("SELECT deptcollid FROM departments WHERE deptid = ? AND deptid <> 0");
        $stmt->execute([$deptid]);
        $d = $stmt->fetch();
        if ($d) $collid = (int)$d['deptcollid'];
    }

    // If dept belongs to a different school than selected, auto-fix to prevent DB errors.
    if ($deptid > 0 && $collid > 0) {
        $stmt = $pdo->prepare("SELECT deptcollid FROM departments WHERE deptid = ? AND deptid <> 0");
        $stmt->execute([$deptid]);
        $d = $stmt->fetch();
        if ($d) $collid = (int)$d['deptcollid'];
    }

    if ($collid > 0) {
        $stmt = $pdo->prepare("SELECT collfullname, collshortname FROM colleges WHERE collid = ? AND collid <> 0");
        $stmt->execute([$collid]);
        $c = $stmt->fetch();
        if ($c) $titleParts[] = $c['collfullname'] . ' (' . $c['collshortname'] . ')';
    }
    if ($deptid > 0) {
        $stmt = $pdo->prepare("SELECT deptfullname FROM departments WHERE deptid = ? AND deptid <> 0");
        $stmt->execute([$deptid]);
        $d = $stmt->fetch();
        if ($d) $titleParts[] = $d['deptfullname'];
    }

    $countSql = "SELECT COUNT(*) FROM programs p WHERE p.progid <> 0";
    $countParams = [];
    if ($collid > 0) { $countSql .= " AND p.progcollid = ?"; $countParams[] = $collid; }
    if ($deptid > 0) { $countSql .= " AND p.progcolldeptid = ?"; $countParams[] = $deptid; }
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($countParams);
    $total = (int)$stmt->fetchColumn();

    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $sql = "
        SELECT p.*,
               c.collfullname, c.collshortname,
               d.deptfullname
        FROM programs p
        LEFT JOIN colleges c ON c.collid = p.progcollid
        LEFT JOIN departments d ON d.deptid = p.progcolldeptid
        WHERE p.progid <> 0
    ";
    $params = [];
    if ($collid > 0) { $sql .= " AND p.progcollid = ?"; $params[] = $collid; }
    if ($deptid > 0) { $sql .= " AND p.progcolldeptid = ?"; $params[] = $deptid; }
    $sql .= " ORDER BY p.progid LIMIT $perPage OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $i => $v) $stmt->bindValue($i + 1, $v, PDO::PARAM_INT);
    $stmt->execute();
    $programs = $stmt->fetchAll();
} catch (Throwable $e) {
    $msg = 'db_error';
    $programs = [];
    $total = 0;
}

$from = $total === 0 ? 0 : ($offset + 1);
$to = min($offset + $perPage, $total);

$pageTitle = 'Program List';
if (!empty($titleParts)) $pageTitle .= ' - ' . implode(' / ', $titleParts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program List - USJ-R SMS</title>
    <link rel="stylesheet" href="../../assets/website.css">
</head>
<body>
    <header class="topbar">
        <a class="topbar-brand" href="../homepage.php">USJ-R School Management System v1.01</a>
        <div class="topbar-right">
            <span class="user-info">You are logged in as: <strong><?= h($user['username'] ?? '') ?></strong> | 👤</span>
            <a href="../../login.php?action=logout" class="btn-logout" style="text-decoration:none;">Logout</a>
        </div>
    </header>

    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="../homepage.php">Home</a></li>
                <li><a href="../schools/schools.php">Schools</a></li>
                <li><a href="../departments/chooseSchool.php">Departments</a></li>
                <li><a href="programs.php" class="active">Programs</a></li>
                <li><a href="../students/students.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../users/users.php">Users</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="section-header">
            <h2><?= h($pageTitle) ?></h2>
        </div>

        <?php if ($msg === 'created'): ?>
            <div class="alert alert-success">✅ Program entry created successfully.</div>
        <?php elseif ($msg === 'updated'): ?>
            <div class="alert alert-success">✅ Program entry updated successfully.</div>
        <?php elseif ($msg === 'deleted'): ?>
            <div class="alert alert-info">🗑️ Program entry deleted.</div>
        <?php elseif ($msg === 'db_error'): ?>
            <div class="alert alert-danger">❌ Database error. Import the SQL and check your DB credentials.</div>
        <?php endif; ?>

        <div class="form-section" style="max-width: 860px; margin-bottom: 14px;">
            <h3 style="margin:0 0 10px;">Select School / Department</h3>
            <form method="GET" action="programs.php" style="max-width: 640px;">
                <div class="form-row">
                    <label>School:</label>
                    <select id="collid" name="collid">
                        <option value="">All Schools</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?= h($s['collid']) ?>" <?= (string)$s['collid'] === (string)$collid ? 'selected' : '' ?>>
                                <?= h($s['collfullname'] . ' (' . $s['collshortname'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span></span>
                </div>

                <div class="form-row">
                    <label>Department:</label>
                    <select id="deptid" name="deptid">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= h($d['deptid']) ?>" data-collid="<?= h($d['deptcollid']) ?>" <?= (string)$d['deptid'] === (string)$deptid ? 'selected' : '' ?>>
                                <?= h($d['deptfullname'] . ' (' . ($d['collshortname'] ?? '-') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span></span>
                </div>

                <div class="form-actions" style="display:flex; gap:8px; margin-top:10px;">
                    <button type="submit" class="btn btn-gray">Apply Filter</button>
                    <a href="programs.php" class="btn btn-gray">Reset</a>
                    <a href="../homepage.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>

        <div style="margin-bottom:14px;">
            <?php if ($canCreate): ?>
                <a href="programCreate.php" class="btn btn-green">➕ Create Program Entry</a>
            <?php endif; ?>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Program ID</th>
                        <th>Program Full Name</th>
                        <th>Program Short Name</th>
                        <th>School</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($programs)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#999;padding:24px;">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($programs as $p): ?>
                            <tr>
                                <td><?= h($p['progid']) ?></td>
                                <td><?= h($p['progfullname']) ?></td>
                                <td><?= h($p['progshortname'] ?: '-') ?></td>
                                <td><?= h(($p['collfullname'] ?? '-') . (($p['collshortname'] ?? '') !== '' ? ' (' . ($p['collshortname'] ?? '') . ')' : '')) ?></td>
                                <td><?= h($p['deptfullname'] ?? '-') ?></td>
                                <td>
                                    <?php if ($canUpdate): ?>
                                        <a href="programUpdate.php?progid=<?= urlencode((string)$p['progid']) ?>" class="btn btn-green btn-sm">✏️ Update</a>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                        <a href="programDelete.php?progid=<?= urlencode((string)$p['progid']) ?>" class="btn btn-red btn-sm">🗑️ Delete</a>
                                    <?php endif; ?>
                                    <?php if (!$canUpdate && !$canDelete): ?>
                                        <span style="color:#999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-top:12px;">
            <div style="color:#666;">
                Showing <?= (int)$from ?>–<?= (int)$to ?> of <?= (int)$total ?>
            </div>
            <div style="display:flex; gap:8px;">
                <?php
                    $prevParams = ['page' => $page - 1];
                    $nextParams = ['page' => $page + 1];
                    if ($collid > 0) { $prevParams['collid'] = $collid; $nextParams['collid'] = $collid; }
                    if ($deptid > 0) { $prevParams['deptid'] = $deptid; $nextParams['deptid'] = $deptid; }
                ?>
                <?php if ($page > 1): ?>
                    <a class="btn btn-gray btn-sm" href="programs.php?<?= h(http_build_query($prevParams)) ?>">Prev</a>
                <?php else: ?>
                    <span class="btn btn-gray btn-sm" style="opacity:.5; pointer-events:none;">Prev</span>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="btn btn-gray btn-sm" href="programs.php?<?= h(http_build_query($nextParams)) ?>">Next</a>
                <?php else: ?>
                    <span class="btn btn-gray btn-sm" style="opacity:.5; pointer-events:none;">Next</span>
                <?php endif; ?>
            </div>
        </div>
</main>

    <script>
        // Simple client-side filter for department options by selected school
        const schoolSelect = document.getElementById('collid');
        const deptSelect = document.getElementById('deptid');

        function filterDeptOptions() {
            const collid = schoolSelect.value;
            const opts = Array.from(deptSelect.querySelectorAll('option[data-collid]'));
            opts.forEach(o => {
                o.hidden = (collid && o.dataset.collid !== collid);
            });
            if (deptSelect.selectedOptions[0]?.hidden) deptSelect.value = '';
        }

        schoolSelect.addEventListener('change', filterDeptOptions);
        filterDeptOptions();
    </script>
</body>
</html>
