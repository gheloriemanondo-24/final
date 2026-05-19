<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();
$isAdmin = strtolower($user['role'] ?? '') === 'administrator' 
        || strtolower($user['role'] ?? '') === 'admin';

$errors = [];
$error = '';
$schools = [];
$collid = (int)($_GET['collid'] ?? 0);
$schoolLabel = '';

try {
    $schools = $pdo->query("SELECT * FROM colleges WHERE collid <> 0 ORDER BY collfullname")->fetchAll();
} catch (Throwable $e) {
    $schools = [];
}

// Find school label AFTER $schools is loaded
foreach ($schools as $s) {
    if ((int)$s['collid'] === $collid) {
        $schoolLabel = $s['collfullname'];
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deptid = (int)($_POST['deptid'] ?? 0);
    $deptfullname = trim($_POST['deptfullname'] ?? '');
    $deptshortname = trim($_POST['deptshortname'] ?? '');
    $deptcollid = (int)($_POST['deptcollid'] ?? 0);

    // REPLACE with:
    $errors = [];
    if ($deptid === 0) {
    $errors['deptid'] = 'Department ID entry cannot be empty';
    // If IDs must start with 21 (5 digits total, like 21001):
    } elseif (!preg_match('/^21\d{3}$/', (string)$deptid)) {
        $errors['deptid'] = 'Invalid ID entry or format';
    }
    if ($deptfullname === '')  $errors['deptfullname']  = 'Department Full Name entry cannot be empty';
    if ($deptshortname === '') $errors['deptshortname'] = 'Department Short Name entry cannot be empty';
    if ($deptcollid === 0)     $errors['deptcollid']    = 'Please select a school';

    if (empty($errors)) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO departments 
            (deptid, deptfullname, deptshortname, deptcollid) 
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $deptid,
            $deptfullname,
            $deptshortname,
            $deptcollid
        ]);

        header('Location: departments.php?msg=created&collid=' . urlencode((string)$deptcollid));
        exit;

    } catch (Throwable $e) {
        $error = 'Could not create department. (Maybe duplicate ID?)';
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Department - USJ-R SMS</title>
    <link rel="stylesheet" href="../../assets/website.css">
</head>
<body>

    <!-- TOPBAR -->
    <header class="topbar">
        <a class="topbar-brand" href="../homepage.php">USJ-R School Management System v1.01</a>
        <div class="topbar-right">
            <span class="user-info">You are logged in as: <strong><?= h($user['username'] ?? '') ?></strong> | 👤</span>
            <a href="../../login.php?action=logout" class="btn-logout" style="text-decoration:none;">Logout</a>
        </div>
    </header>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="../homepage.php">Home</a></li>
                <li><a href="../schools/schools.php">Schools</a></li>
                <li><a href="departments.php" class="active">Departments</a></li>
                <li><a href="../programs/programs.php">Programs</a></li>
                <li><a href="../students/students.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../users/users.php">Users</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div style="padding: 8px 0; max-width: 860px;">
            
            <div class="section-header">
                <h2>Department Create<?= $collid > 0 && $schoolLabel ? ' - ' . $collid . ': ' . h($schoolLabel) : '' ?></h2>
            </div>


            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <form id="deptCreateForm" method="POST" action="departmentCreate.php?collid=<?= $collid ?>">
                <!-- Department ID -->
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Department ID:</label>
                    <input type="number" id="deptid" name="deptid" value="<?= h($_POST['deptid'] ?? '') ?>">
                    <span class="error-msg" style="color:red;"><?= h($errors['deptid'] ?? '') ?></span>
                </div>

                <!-- Department Full Name -->
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Department Full Name:</label>
                    <input type="text" id="deptfullname" name="deptfullname" value="<?= h($_POST['deptfullname'] ?? '') ?>">
                    <span class="error-msg" style="color:red;"><?= h($errors['deptfullname'] ?? '') ?></span>
                </div>

                <!-- Department Short Name -->
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Department Short Name:</label>
                    <input type="text" id="deptshortname" name="deptshortname" value="<?= h($_POST['deptshortname'] ?? '') ?>">
                    <span class="error-msg" style="color:red;"><?= h($errors['deptshortname'] ?? '') ?></span>
                </div>

                <input type="hidden" name="deptcollid" value="<?= h($collid) ?>">
                <!-- Form Actions -->
                <div class="form-actions" style="display:flex; gap:8px; margin-top:16px;">
                    <button type="submit" class="btn btn-green">Save New Department Entry</button>
                    <a href="departmentCreate.php?collid=<?= $collid ?>" class="btn btn-gray">Reset Form</a>
                    <a href="departments.php?collid=<?= $collid?> " class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>