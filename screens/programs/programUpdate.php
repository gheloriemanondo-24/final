<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();
$isAdmin = strtolower($user['role'] ?? '') === 'administrator' 
        || strtolower($user['role'] ?? '') === 'admin';

$progid = (int)($_GET['progid'] ?? 0);
$error = '';
$schools = [];
$departments = [];

try {
    $schools = $pdo->query("SELECT * FROM colleges ORDER BY collfullname")->fetchAll();
} catch (Throwable $e) {
    $schools = [];
}

try {
    $departments = $pdo->query("
        SELECT d.*, c.collshortname
        FROM departments d
        LEFT JOIN colleges c ON c.collid = d.deptcollid
        ORDER BY d.deptfullname
    ")->fetchAll();
} catch (Throwable $e) {
    $departments = [];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM programs WHERE progid = ?");
    $stmt->execute([$progid]);
    $prog = $stmt->fetch();
} catch (Throwable $e) {
    $prog = null;
}

if (!$prog) {
    header('Location: programs.php?msg=db_error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $progfullname = trim($_POST['progfullname'] ?? '');
    $progshortname = trim($_POST['progshortname'] ?? '');
    $progcollid = (int)($_POST['progcollid'] ?? 0);
    $progcolldeptid = (int)($_POST['progcolldeptid'] ?? 0);

    if ($progfullname === '' || $progcollid === 0 || $progcolldeptid === 0) {
        $error = 'Program Full Name, School, and Department are required.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE programs SET progfullname = ?, progshortname = ?, progcollid = ?, progcolldeptid = ? WHERE progid = ?");
            $stmt->execute([$progfullname, $progshortname, $progcollid, $progcolldeptid, $progid]);
            header('Location: programs.php?msg=updated&collid=' . urlencode((string)$progcollid) . '&deptid=' . urlencode((string)$progcolldeptid));
            exit;
        } catch (Throwable $e) {
            $error = 'Could not update program.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Program - USJ-R SMS</title>
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
                <li><a href="../departments/departments.php">Departments</a></li>
                <li><a href="programs.php" class="active">Programs</a></li>
                <li><a href="../students/students.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../users/users.php">Users</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div style="padding: 8px 0; max-width: 900px;">
            <div class="section-header">
                <h2>Program Update</h2>
            </div>

            <div class="alert alert-info">ℹ️ Basic PHP update (updates database).</div>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <form id="progUpdateForm" method="POST" action="programUpdate.php?progid=<?= urlencode((string)$progid) ?>">
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Select School:</label>
                    <?php $selectedSchool = (string)($_POST['progcollid'] ?? $prog['progcollid']); ?>
                    <select id="progcollid" name="progcollid">
                        <option value="">Select School</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?= h($s['collid']) ?>" <?= (string)$s['collid'] === $selectedSchool ? 'selected' : '' ?>>
                                <?= h($s['collfullname'] . ' (' . $s['collshortname'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg"></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Select Department:</label>
                    <?php $selectedDept = (string)($_POST['progcolldeptid'] ?? $prog['progcolldeptid']); ?>
                    <select id="progcolldeptid" name="progcolldeptid">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= h($d['deptid']) ?>" data-collid="<?= h($d['deptcollid']) ?>" <?= (string)$d['deptid'] === $selectedDept ? 'selected' : '' ?>>
                                <?= h($d['deptfullname'] . ' (' . ($d['collshortname'] ?? '-') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg"></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Program ID:</label>
                    <input type="number" id="progid" readonly value="<?= h($prog['progid']) ?>">
                    <span class="error-msg"></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Program Full Name:</label>
                    <input type="text" id="progfullname" name="progfullname" placeholder="e.g. Bachelor of Science in ..." value="<?= h($_POST['progfullname'] ?? $prog['progfullname']) ?>">
                    <span class="error-msg" id="err-name"></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Program Short Name:</label>
                    <input type="text" id="progshortname" name="progshortname" placeholder="e.g. BSCS" value="<?= h($_POST['progshortname'] ?? $prog['progshortname']) ?>">
                    <span class="error-msg"></span>
                </div>

                <div class="form-actions" style="display:flex; gap:8px; margin-top:16px;">
                    <button type="submit" class="btn btn-green">Update Program Entry</button>
                    <a href="programUpdate.php?progid=<?= urlencode((string)$progid) ?>" class="btn btn-gray">Reset Form</a>
                    <a href="programs.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        // simple client-side filter for department options by selected school
        const schoolSelect = document.getElementById('progcollid');
        const deptSelect = document.getElementById('progcolldeptid');
        function filterDeptOptions() {
            const collid = schoolSelect.value;
            const opts = Array.from(deptSelect.querySelectorAll('option[data-collid]'));
            opts.forEach(o => o.hidden = (collid && o.dataset.collid !== collid));
            if (deptSelect.selectedOptions[0]?.hidden) deptSelect.value = '';
        }
        schoolSelect.addEventListener('change', filterDeptOptions);
        filterDeptOptions();
    </script>
</body>
</html>