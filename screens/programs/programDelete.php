<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();
$isAdmin = strtolower($user['role'] ?? '') === 'administrator' 
        || strtolower($user['role'] ?? '') === 'admin';

$progid = (int)($_GET['progid'] ?? 0);
$error = '';

try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.collfullname, c.collshortname, d.deptfullname
        FROM programs p
        LEFT JOIN colleges c ON c.collid = p.progcollid
        LEFT JOIN departments d ON d.deptid = p.progcolldeptid
        WHERE p.progid = ?
    ");
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
    try {
        $stmt = $pdo->prepare("DELETE FROM programs WHERE progid = ?");
        $stmt->execute([$progid]);
        header('Location: programs.php?msg=deleted&collid=' . urlencode((string)$prog['progcollid']) . '&deptid=' . urlencode((string)$prog['progcolldeptid']));
        exit;
    } catch (Throwable $e) {
        $error = 'Cannot delete this program (it may have linked students).';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Program - USJ-R SMS</title>
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
        <div class="form-section" style="max-width: 760px;">
            <h2>Confirm Delete</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <div class="alert alert-danger">
                ⚠️ Are you sure you want to delete this program?
            </div>

            <p style="margin-bottom: 16px;">
                Program ID: <strong><?= h($prog['progid']) ?></strong><br>
                Program Name: <strong><?= h($prog['progfullname']) ?></strong><br>
                School: <strong><?= h(($prog['collfullname'] ?? '-') . ' (' . ($prog['collshortname'] ?? '-') . ')') ?></strong><br>
                Department: <strong><?= h($prog['deptfullname'] ?? '-') ?></strong>
            </p>

            <form method="POST" action="programDelete.php?progid=<?= urlencode((string)$progid) ?>" class="form-actions">
                <a href="programs.php" class="btn btn-gray">Cancel</a>
                <button type="submit" class="btn btn-red">Yes, Delete</button>
            </form>
        </div>
    </main>
</body>
</html>