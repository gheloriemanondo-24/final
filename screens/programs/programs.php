<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();
$isAdmin = strtolower($user['role'] ?? '') === 'administrator' 
        || strtolower($user['role'] ?? '') === 'admin';
        
$collid = (int)($_GET['collid'] ?? 0);
$deptid = (int)($_GET['deptid'] ?? 0);
$schools = [];
$departments = [];

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program List - USJ-R SMS</title>
    <link rel="stylesheet" href="../../assets/website.css">
    <style>
        #collid:valid ~ * #btn-dept {
            background: var(--green);
            color: white;
        }
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
        <h2>Select School and Department</h2>
    </div>

    <form method="GET" action="programs.php">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
            <select id="collid" name="collid" style="width:300px; height:38px;"required>
                <option value="">Select School</option>
                <?php foreach ($schools as $s): ?>
                    <option value="<?= h($s['collid']) ?>" <?= (string)$s['collid'] === (string)$collid ? 'selected' : '' ?>>
                       <?= h($s['collfullname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-green" style="width:148px; height:36px; text-align:center; justify-content:center;">Select School</button>
        </div>

        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
            <select id="deptid" name="deptid" style="width:300px; height:36px;" <?= $collid === 0 ? 'disabled' : '' ?>>
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
            <button type="submit" class="btn btn-green" style="width:148px; height:38px;" <?= $collid === 0 ? 'disabled' : '' ?>>Select Department</button>
        </div>
    </form>
</main>

    <script>
        // Dependency logic:
        // - If school is selected, department list is filtered to that school.
        // - If school is not selected yet, user can still choose any department.
        // - If a department is selected, school will auto-select to match it.
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

        function syncSchoolFromDept() {
            const selected = deptSelect.selectedOptions[0];
            const deptCollid = selected?.dataset?.collid;
            if (deptCollid && !schoolSelect.value) {
                schoolSelect.value = deptCollid;
            }
        }

        schoolSelect.addEventListener('change', filterDeptOptions);
        deptSelect.addEventListener('change', () => {
            syncSchoolFromDept();
            filterDeptOptions();
        });

        syncSchoolFromDept();
        filterDeptOptions();
    </script>
</body>
</html>