<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();
$isAdmin = strtolower($user['role'] ?? '') === 'administrator' 
        || strtolower($user['role'] ?? '') === 'admin';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Programs - USJ-R SMS</title>
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
        <div class="form-section" style="max-width: 860px;">
            <h2>Filter Programs</h2>

            <div class="alert alert-info">
                ℹ️ Select a school and/or department to filter the program list.
            </div>

            <form method="GET" action="programs.php" style="max-width: 640px;">
                <div class="form-row">
                    <label>School:</label>
                    <select id="collid" name="collid">
                        <option value="">All Schools</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?= h($s['collid']) ?>">
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
                            <option value="<?= h($d['deptid']) ?>" data-collid="<?= h($d['deptcollid']) ?>">
                                <?= h($d['deptfullname'] . ' (' . ($d['collshortname'] ?? 'OTH') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span></span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-green">Apply Filter</button>
                    <a href="programs.php" class="btn btn-gray">View All</a>
                    <a href="programs.php" class="btn btn-red">Exit</a>
                </div>
            </form>

            <p class="total-row">Tip: direct URL example: <code>programs.php?collid=3&deptid=3001</code></p>
        </div>
    </main>

    <script>
        const schoolSelect = document.getElementById('collid');
        const deptSelect = document.getElementById('deptid');

        function filterDeptOptions() {
            const collid = schoolSelect.value;
            const opts = Array.from(deptSelect.querySelectorAll('option[data-collid]'));
            opts.forEach(o => {
                o.hidden = (collid && o.dataset.collid !== collid);
            });
            // if selected option is now hidden, reset to All
            if (deptSelect.selectedOptions[0]?.hidden) deptSelect.value = '';
        }

        schoolSelect.addEventListener('change', filterDeptOptions);
        filterDeptOptions();
    </script>
</body>
</html>