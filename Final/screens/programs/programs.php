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
$action = (string)($_GET['action'] ?? ''); // school | department
$error = '';
$errors = [];
$schoolConfirmed = false;
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
    // Two-step UI:
    // - selecting a school should NOT open another page; it only enables the department dropdown.
    // - action=department: show the program list for the selected school+department.
    if ($action === 'school') {
        $deptid = 0;
        if ($collid <= 0) {
            $errors['collid'] = 'Please select a School.';
        } else {
            $schoolConfirmed = true;
        }
    }
    if ($action === 'department') {
        $page = 1;
    }

    // Only show the list AFTER the user selects a DEPARTMENT (or after a CRUD redirect message).
    // Selecting a school should only enable the department dropdown (stay on the selector UI).
    $shouldList = ($deptid > 0 || (string)$msg !== '');

    // Server-side validation for filter actions
    if ($action === 'department') {
        if ($collid <= 0) $errors['collid'] = 'Please select a School.';
        if ($deptid <= 0) $errors['deptid'] = 'Please select a Department.';
        if (!empty($errors)) {
            $error = 'Please fix the highlighted fields.';
            $shouldList = false;
        } else {
            $schoolConfirmed = true;
        }
    }

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

    if ($shouldList) {
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
    } else {
        $programs = [];
        $total = 0;
        $totalPages = 1;
        $offset = 0;
    }
} catch (Throwable $e) {
    $msg = 'db_error';
    $programs = [];
    $total = 0;
}

$from = $total === 0 ? 0 : ($offset + 1);
$to = min($offset + $perPage, $total);

$pageTitle = 'Select School and Department';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program List - USJ-R SMS</title>
    <link rel="stylesheet" href="../../assets/website.css">
    <style>
        /* Layout-only tweaks to match the requested Programs selector design */
        #collid, #deptid { width: 300px; }
    </style>
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

        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($msg === 'created'): ?>
            <div class="alert alert-success">✅ Program entry created successfully.</div>
        <?php elseif ($msg === 'updated'): ?>
            <div class="alert alert-success">✅ Program entry updated successfully.</div>
        <?php elseif ($msg === 'deleted'): ?>
            <div class="alert alert-info">🗑️ Program entry deleted.</div>
        <?php elseif ($msg === 'db_error'): ?>
            <div class="alert alert-danger">❌ Database error. Import the SQL and check your DB credentials.</div>
        <?php endif; ?>

        <form method="GET" action="programs.php" id="programFilterForm">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <select id="collid" name="collid" style="height:38px;">
                    <option value="">Select School</option>
                    <?php foreach ($schools as $s): ?>
                        <option value="<?= h($s['collid']) ?>" <?= (string)$s['collid'] === (string)$collid ? 'selected' : '' ?>>
                           <?= h($s['collfullname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button
                    id="btn-school"
                    type="submit"
                    name="action"
                    value="school"
                    class="btn btn-green"
                    style="width:148px; height:36px; text-align:center; justify-content:center;"
                >
                    Select School
                </button>
                <span class="error-msg" style="color:red; min-width: 260px;"><?= h($errors['collid'] ?? '') ?></span>
            </div>

            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <select id="deptid" name="deptid" style="height:36px;" <?= $schoolConfirmed ? '' : 'disabled' ?>>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $d): ?>
                        <option
                            value="<?= h($d['deptid']) ?>"
                            data-collid="<?= h($d['deptcollid']) ?>"
                            <?= (string)$d['deptid'] === (string)$deptid ? 'selected' : '' ?>
                        >
                            <?= h($d['deptfullname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button
                    id="btn-dept"
                    type="submit"
                    name="action"
                    value="department"
                    class="btn btn-green"
                    style="width:148px; height:38px;"
                    <?= $schoolConfirmed ? '' : 'disabled' ?>
                >
                    Select Department
                </button>
                <span class="error-msg" style="color:red; min-width: 260px;"><?= h($errors['deptid'] ?? '') ?></span>
            </div>
        </form>

        <?php if ($collid > 0 && $deptid === 0): ?>
            <div style="margin: 6px 0 14px;">
                <a href="programs.php" class="btn btn-red">🔙 Back</a>
            </div>
        <?php endif; ?>

        <?php if ($deptid > 0 || (string)$msg !== ''): ?>
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
        <?php endif; ?>
</main>

    <script>
        // Client-side behavior (match old "select school first" flow):
        // - After selecting a school, user must click "Select School" to enable department dropdown.
        // - Changing the school disables department again (requires clicking "Select School" again).
        const schoolSelect = document.getElementById('collid');
        const deptSelect = document.getElementById('deptid');
        const deptBtn = document.getElementById('btn-dept');

        function setDeptEnabled(enabled) {
            deptSelect.disabled = !enabled;
            if (deptBtn) deptBtn.disabled = !enabled;
        }

        function filterDeptOptions() {
            const collid = schoolSelect.value;
            const opts = Array.from(deptSelect.querySelectorAll('option[data-collid]'));
            opts.forEach(o => {
                o.hidden = (collid && o.dataset.collid !== collid);
            });
            if (deptSelect.selectedOptions[0]?.hidden) deptSelect.value = '';
        }

        // Changing the school resets department and requires clicking "Select School" again.
        schoolSelect.addEventListener('change', () => {
            deptSelect.value = '';
            setDeptEnabled(false);
        });

        // On initial load, keep department availability as rendered by server.
        // Still ensure the options are filtered based on the currently selected school.
        filterDeptOptions();
    </script>
</body>
</html>
