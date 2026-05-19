<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('view', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');
$canCreate = can('create');
$canUpdate = can('update');
$canDelete = can('delete');
$msg = $_GET['msg'] ?? '';
$collid = (int)($_GET['collid'] ?? 0);
$deptid = (int)($_GET['deptid'] ?? 0);
$progid = (int)($_GET['progid'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$total = 0;
$totalPages = 1;
$offset = 0;

$schools = [];
$departments = [];
$programs = [];
$students = [];
$titleParts = [];

try {
    $schools = $pdo->query("SELECT * FROM colleges WHERE collid <> 0 ORDER BY collfullname")->fetchAll();
} catch (Throwable $e) {
    $schools = [];
}

try {
    $departments = $pdo->query("
        SELECT d.*, c.collshortname
        FROM departments d
        LEFT JOIN colleges c ON c.collid = d.deptcollid
        WHERE d.deptid <> 0
        ORDER BY d.deptfullname
    ")->fetchAll();
} catch (Throwable $e) {
    $departments = [];
}

try {
    $programs = $pdo->query("
        SELECT p.*, c.collshortname, d.deptfullname
        FROM programs p
        LEFT JOIN colleges c ON c.collid = p.progcollid
        LEFT JOIN departments d ON d.deptid = p.progcolldeptid
        WHERE p.progid <> 0
        ORDER BY p.progfullname
    ")->fetchAll();
} catch (Throwable $e) {
    $programs = [];
}

try {
    // Show the table ONLY after user applies a filter OR after a CRUD redirect message.
    // (So clicking Reset clears filters AND hides the list.)
    $shouldList = ($collid > 0 || $deptid > 0 || $progid > 0 || (string)$msg !== '');

    // Infer school/department from selected program (if needed)
    if ($progid > 0 && ($deptid === 0 || $collid === 0)) {
        $stmt = $pdo->prepare("SELECT progcollid, progcolldeptid FROM programs WHERE progid = ? AND progid <> 0");
        $stmt->execute([$progid]);
        $p = $stmt->fetch();
        if ($p) {
            if ($collid === 0) $collid = (int)$p['progcollid'];
            if ($deptid === 0) $deptid = (int)$p['progcolldeptid'];
        }
    }

    // If a department is chosen but no school yet, infer the school.
    if ($deptid > 0 && $collid === 0) {
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
    if ($progid > 0) {
        $stmt = $pdo->prepare("SELECT progfullname FROM programs WHERE progid = ? AND progid <> 0");
        $stmt->execute([$progid]);
        $p = $stmt->fetch();
        if ($p) $titleParts[] = $p['progfullname'];
    }

    if ($shouldList) {
        $countSql = "SELECT COUNT(*) FROM students s WHERE s.studid <> 0";
        $countParams = [];
        if ($collid > 0) { $countSql .= " AND s.studcollid = ?"; $countParams[] = $collid; }
        if ($deptid > 0) { $countSql .= " AND s.studcolldeptid = ?"; $countParams[] = $deptid; }
        if ($progid > 0) { $countSql .= " AND s.studprogid = ?"; $countParams[] = $progid; }
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($countParams);
        $total = (int)$stmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT s.*,
                   c.collfullname, c.collshortname,
                   d.deptfullname,
                   p.progfullname, p.progshortname
            FROM students s
            LEFT JOIN colleges c ON c.collid = s.studcollid
            LEFT JOIN departments d ON d.deptid = s.studcolldeptid
            LEFT JOIN programs p ON p.progid = s.studprogid
            WHERE s.studid <> 0
        ";
        $params = [];
        if ($collid > 0) { $sql .= " AND s.studcollid = ?"; $params[] = $collid; }
        if ($deptid > 0) { $sql .= " AND s.studcolldeptid = ?"; $params[] = $deptid; }
        if ($progid > 0) { $sql .= " AND s.studprogid = ?"; $params[] = $progid; }
        $sql .= " ORDER BY s.studid LIMIT $perPage OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $i => $v) $stmt->bindValue($i + 1, $v, PDO::PARAM_INT);
        $stmt->execute();
        $students = $stmt->fetchAll();
    } else {
        $total = 0;
        $students = [];
        $totalPages = 1;
        $offset = 0;
    }
} catch (Throwable $e) {
    $msg = 'db_error';
    $students = [];
    $total = 0;
}

$from = $total === 0 ? 0 : ($offset + 1);
$to = min($offset + $perPage, $total);

$pageTitle = 'Student List';
if (!empty($titleParts)) $pageTitle = 'Student List - ' . implode(' / ', $titleParts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List - USJ-R SMS</title>
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
                <li><a href="../programs/programs.php">Programs</a></li>
                <li><a href="students.php" class="active">Students</a></li>
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
            <div class="alert alert-success">✅ Student entry created successfully.</div>
        <?php elseif ($msg === 'updated'): ?>
            <div class="alert alert-success">✅ Student entry updated successfully.</div>
        <?php elseif ($msg === 'deleted'): ?>
            <div class="alert alert-info">🗑️ Student entry deleted.</div>
        <?php elseif ($msg === 'db_error'): ?>
            <div class="alert alert-danger">❌ Database error. Import the SQL and check your DB credentials.</div>
        <?php endif; ?>

        <div class="form-section" style="max-width: 980px; margin-bottom: 14px;">
            <h3 style="margin:0 0 10px;">Select School / Department / Program</h3>
            <form method="GET" action="students.php" style="max-width: 760px;">
                <div class="form-row">
                    <label>Select School:</label>
                    <select id="collid" name="collid">
                        <option value="">Select School</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?= h($s['collid']) ?>" <?= (string)$s['collid'] === (string)$collid ? 'selected' : '' ?>>
                                <?= h($s['collfullname'] . ' (' . $s['collshortname'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span></span>
                </div>

                <div class="form-row">
                    <label>Select Department:</label>
                    <select id="deptid" name="deptid">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option
                                value="<?= h($d['deptid']) ?>"
                                data-collid="<?= h($d['deptcollid']) ?>"
                                <?= (string)$d['deptid'] === (string)$deptid ? 'selected' : '' ?>
                            >
                                <?= h($d['deptfullname'] . ' (' . ($d['collshortname'] ?? '-') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span></span>
                </div>

                <div class="form-row">
                    <label>Select Program:</label>
                    <select id="progid" name="progid">
                        <option value="">Select Program</option>
                        <?php foreach ($programs as $p): ?>
                            <option
                                value="<?= h($p['progid']) ?>"
                                data-collid="<?= h($p['progcollid']) ?>"
                                data-deptid="<?= h($p['progcolldeptid']) ?>"
                                <?= (string)$p['progid'] === (string)$progid ? 'selected' : '' ?>
                            >
                                <?= h(($p['progfullname']) . ($p['progshortname'] ? ' (' . $p['progshortname'] . ')' : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span></span>
                </div>

                <div class="form-actions" style="display:flex; gap:8px; margin-top:10px;">
                    <button type="submit" class="btn btn-gray">Apply Filter</button>
                    <a href="students.php" class="btn btn-gray">Reset</a>
                    <a href="../homepage.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>

        <div style="margin-bottom:14px;">
            <?php if ($canCreate): ?>
                <a href="studentCreate.php" class="btn btn-green">➕ Create Student Entry</a>
            <?php endif; ?>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Stud ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>School</th>
                        <th>Dept</th>
                        <th>Program</th>
                        <th>Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="9" style="text-align:center;color:#999;padding:24px;">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?= h($s['studid']) ?></td>
                                <td><?= h($s['studlastname']) ?></td>
                                <td><?= h($s['studfirstname']) ?></td>
                                <td><?= h($s['studmidname'] ?? '') ?></td>
                                <td><?= h(($s['collfullname'] ?? '-') . (($s['collshortname'] ?? '') !== '' ? ' (' . ($s['collshortname'] ?? '') . ')' : '')) ?></td>
                                <td><?= h(($s['deptfullname'] ?? '-')) ?></td>
                                <td><?= h(($s['progfullname'] ?? '-')) ?></td>
                                <td><?= h($s['studyear']) ?></td>
                                <td>
                                    <?php if ($canUpdate): ?>
                                        <a href="studentUpdate.php?studid=<?= urlencode((string)$s['studid']) ?>" class="btn btn-green btn-sm">✏️ Update</a>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                        <a href="studentDelete.php?studid=<?= urlencode((string)$s['studid']) ?>" class="btn btn-red btn-sm">🗑️ Delete</a>
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
                    if ($progid > 0) { $prevParams['progid'] = $progid; $nextParams['progid'] = $progid; }
                ?>
                <?php if ($page > 1): ?>
                    <a class="btn btn-gray btn-sm" href="students.php?<?= h(http_build_query($prevParams)) ?>">Prev</a>
                <?php else: ?>
                    <span class="btn btn-gray btn-sm" style="opacity:.5; pointer-events:none;">Prev</span>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="btn btn-gray btn-sm" href="students.php?<?= h(http_build_query($nextParams)) ?>">Next</a>
                <?php else: ?>
                    <span class="btn btn-gray btn-sm" style="opacity:.5; pointer-events:none;">Next</span>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Dependency logic:
        // - If school is selected, department list is filtered to that school.
        // - If school is not selected yet, user can still choose any department.
        // - Program list depends on selected school + selected department.
        // - If a program is selected, school/department will auto-select to match it.
        const schoolSelect = document.getElementById('collid');
        const deptSelect = document.getElementById('deptid');
        const progSelect = document.getElementById('progid');

        function filterDeptOptions() {
            const collid = schoolSelect.value;
            const opts = Array.from(deptSelect.querySelectorAll('option[data-collid]'));
            opts.forEach(o => o.hidden = (collid && o.dataset.collid !== collid));
            if (deptSelect.selectedOptions[0]?.hidden) deptSelect.value = '';
        }

        function filterProgramOptions() {
            const collid = schoolSelect.value;
            const deptid = deptSelect.value;
            const opts = Array.from(progSelect.querySelectorAll('option[data-collid]'));
            opts.forEach(o => {
                // If school not chosen, allow user to choose program (do not hide yet)
                if (!collid) { o.hidden = false; return; }
                const matchSchool = o.dataset.collid === collid;
                const matchDept = !deptid || o.dataset.deptid === deptid;
                o.hidden = !(matchSchool && matchDept);
            });
            if (progSelect.selectedOptions[0]?.hidden) progSelect.value = '';
        }

        function syncFromDept() {
            const selected = deptSelect.selectedOptions[0];
            const deptCollid = selected?.dataset?.collid;
            if (deptCollid && !schoolSelect.value) schoolSelect.value = deptCollid;
        }

        function syncFromProgram() {
            const selected = progSelect.selectedOptions[0];
            const pCollid = selected?.dataset?.collid;
            const pDeptid = selected?.dataset?.deptid;
            if (pCollid) schoolSelect.value = pCollid;
            if (pDeptid) deptSelect.value = pDeptid;
        }

        schoolSelect.addEventListener('change', () => {
            filterDeptOptions();
            filterProgramOptions();
        });
        deptSelect.addEventListener('change', () => {
            syncFromDept();
            filterDeptOptions();
            filterProgramOptions();
        });
        progSelect.addEventListener('change', () => {
            syncFromProgram();
            filterDeptOptions();
            filterProgramOptions();
        });

        // initial
        syncFromProgram();
        syncFromDept();
        filterDeptOptions();
        filterProgramOptions();
    </script>
</body>
</html>
