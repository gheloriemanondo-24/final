<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();
$isAdmin = strtolower($user['role'] ?? '') === 'administrator' 
        || strtolower($user['role'] ?? '') === 'admin';

$collid = (int)($_GET['collid'] ?? 0);
$error = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM colleges WHERE collid = ?");
    $stmt->execute([$collid]);
    $school = $stmt->fetch();
} catch (Throwable $e) {
    $school = null;
}

if (!$school) {
    header('Location: schools.php?msg=db_error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM colleges WHERE collid = ?");
        $stmt->execute([$collid]);
        header('Location: schools.php?msg=deleted');
        exit;
    } catch (Throwable $e) {
        $error = 'Cannot delete this school (it may have linked departments/programs/students).';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete School - USJ-R SMS</title>
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
                <li><a href="schools.php" class="active">Schools</a></li>
                <li><a href="../departments/departments.php">Departments</a></li>
                <li><a href="../programs/programs.php">Programs</a></li>
                <li><a href="../students/students.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../users/users.php">Users</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="form-section" style="max-width: 760px;">
            <h2>You are about to delete the following school</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <div class="alert alert-danger">
                You are about to delete the following school entry:
            </div>

            <div class="table-wrap" style="margin: 12px 0;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 240px;">Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>School ID:</strong></td>
                            <td><?= h($school['collid']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>School Full Name:</strong></td>
                            <td><?= h($school['collfullname']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>School Short Name:</strong></td>
                            <td><?= h($school['collshortname'] ?? '-') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-danger">Are you sure you want to delete this school entry?</div>

            <form method="POST" action="schoolDelete.php?collid=<?= urlencode((string)$collid) ?>" class="form-actions" style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-red">Yes, Delete Entry</button>
                <a href="schools.php" class="btn btn-gray">No, Cancel</a>
            </form>
        </div>
    </main>
</body>
</html>