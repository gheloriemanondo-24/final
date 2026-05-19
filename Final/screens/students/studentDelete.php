<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('delete', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');

$studid = (int)($_GET['studid'] ?? 0);
$error = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE studid = ?");
    $stmt->execute([$studid]);
    $stud = $stmt->fetch();
} catch (Throwable $e) {
    $stud = null;
}

if (!$stud) {
    header('Location: students.php?msg=db_error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE studid = ?");
        $stmt->execute([$studid]);
        header('Location: students.php?msg=deleted');
        exit;
    } catch (Throwable $e) {
        $error = 'Cannot delete this student.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Student - USJ-R SMS</title>
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
        <div class="form-section" style="max-width: 760px;">
            <h2>Confirm Delete</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <div class="alert alert-danger">
                You are about to delete the following student entry:
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
                            <td><strong>Student ID</strong></td>
                            <td><?= h($stud['studid']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Student First Name</strong></td>
                            <td><?= h($stud['studfirstname']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Student Middle Name</strong></td>
                            <td><?= h($stud['studmidname'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Student Last Name</strong></td>
                            <td><?= h($stud['studlastname']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p style="margin: 6px 0 14px; color:#666;">
                Are you sure you want to delete this student entry?
            </p>

            <form method="POST" action="studentDelete.php?studid=<?= urlencode((string)$studid) ?>" class="form-actions" style="display:flex; gap:8px;">
                <a href="students.php" class="btn btn-gray">Cancel Operation</a>
                <button type="submit" class="btn btn-red">Proceed</button>
            </form>
        </div>
    </main>
</body>
</html>
