<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('create', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');

$error = '';
$errors = [];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $progidRaw = (string)($_POST['progid'] ?? '');
    $progfullname = trim((string)($_POST['progfullname'] ?? ''));
    $progshortname = trim((string)($_POST['progshortname'] ?? ''));
    $progcollid = (int)($_POST['progcollid'] ?? 0);
    $progcolldeptid = (int)($_POST['progcolldeptid'] ?? 0);

    // Validate (match style used in Schools/Departments)
    $errors = [];
    if (trim($progidRaw) === '') {
        $errors['progid'] = 'Program ID entry cannot be empty';
    } elseif (!isDigitsOnly($progidRaw)) {
        $errors['progid'] = 'Program ID must contain numbers only';
    } elseif (!preg_match('/^\d{10}$/', $progidRaw)) {
        $errors['progid'] = 'Program ID must be exactly 10 digits';
    }
    if ($progcollid === 0) $errors['progcollid'] = 'Please select a School.';
    if ($progcolldeptid === 0) $errors['progcolldeptid'] = 'Please select a Department.';
    if ($progfullname === '') $errors['progfullname'] = 'Program Full Name entry cannot be empty';

    if (!empty($errors)) {
        $error = 'Please fix the highlighted fields.';
    } else {
        try {
            // Server-side check: department must belong to selected school
            $stmt = $pdo->prepare("SELECT deptcollid FROM departments WHERE deptid = ? AND deptid <> 0");
            $stmt->execute([$progcolldeptid]);
            $d = $stmt->fetch();
            if (!$d) {
                throw new RuntimeException('Invalid department');
            }
            if ((int)$d['deptcollid'] !== $progcollid) {
                $errors['progcolldeptid'] = 'Selected Department does not belong to the selected School.';
                $error = 'Please fix the highlighted fields.';
            } else {
                $progid = (int)$progidRaw;
                $stmt = $pdo->prepare("INSERT INTO programs (progid, progfullname, progshortname, progcollid, progcolldeptid) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$progid, $progfullname, $progshortname, $progcollid, $progcolldeptid]);
                // PRG: do not keep filter selections after create
                header('Location: programs.php?msg=created');
                exit;
            }
        } catch (Throwable $e) {
            if ($error === '') $error = 'Could not create program. (Maybe duplicate ID?)';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Program - USJ-R SMS</title>
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
        <div style="padding: 8px 0; max-width: 900px;">
            <div class="section-header">
                <h2>Program Create</h2>
            </div>

            <div class="alert alert-info">ℹ️ Basic PHP create (inserts into database).</div>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <form id="progCreateForm" method="POST" action="programCreate.php">
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Select School:</label>
                    <select id="progcollid" name="progcollid">
                        <option value="">Select School</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?= h($s['collid']) ?>" <?= (string)$s['collid'] === (string)($_POST['progcollid'] ?? '') ? 'selected' : '' ?>>
                                <?= h($s['collfullname'] . ' (' . $s['collshortname'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg" id="err-school"><?= h($errors['progcollid'] ?? '') ?></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Select Department:</label>
                    <select id="progcolldeptid" name="progcolldeptid">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= h($d['deptid']) ?>" data-collid="<?= h($d['deptcollid']) ?>" <?= (string)$d['deptid'] === (string)($_POST['progcolldeptid'] ?? '') ? 'selected' : '' ?>>
                                <?= h($d['deptfullname'] . ' (' . ($d['collshortname'] ?? '-') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg" id="err-dept"><?= h($errors['progcolldeptid'] ?? '') ?></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Program ID:</label>
                    <input type="text" id="progid" name="progid" value="<?= h($_POST['progid'] ?? '') ?>">
                    <?php if (!empty($errors['progid'])): ?>
                        <span class="error-msg" id="err-id" style="color:red;"><?= h($errors['progid'] ?? '') ?></span>
                    <?php else: ?>
                        <span class="error-msg" id="err-id" style="color:#666;">Format: exactly 10 digits (example: 2111001001)</span>
                    <?php endif; ?>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Program Full Name:</label>
                    <input type="text" id="progfullname" name="progfullname" value="<?= h($_POST['progfullname'] ?? '') ?>">
                    <span class="error-msg" id="err-name"><?= h($errors['progfullname'] ?? '') ?></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Program Short Name:</label>
                    <input type="text" id="progshortname" name="progshortname" value="<?= h($_POST['progshortname'] ?? '') ?>">
                    <span class="error-msg"></span>
                </div>

                <div class="form-actions" style="display:flex; gap:8px; margin-top:16px;">
                    <button type="submit" class="btn btn-green">Save New Program Entry</button>
                    <a href="programCreate.php" class="btn btn-gray">Reset Form</a>
                    <a href="programs.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        const schoolSelect = document.getElementById('progcollid');
        const deptSelect = document.getElementById('progcolldeptid');

        function filterDeptOptions() {
            const school = schoolSelect.value;
            const opts = Array.from(deptSelect.querySelectorAll('option[data-collid]'));
            opts.forEach(o => o.hidden = (school && o.dataset.collid !== school));
            if (deptSelect.selectedOptions[0]?.hidden) deptSelect.value = '';
        }
        schoolSelect.addEventListener('change', filterDeptOptions);
        filterDeptOptions();
    </script>
</body>
</html>
