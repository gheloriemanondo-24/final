<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('delete', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');

$deptid = (int)($_GET['deptid'] ?? 0);
$error = '';
$programCount = 0;

try {

    $stmt = $pdo->prepare("
        SELECT d.*, c.collfullname, c.collshortname
        FROM departments d
        LEFT JOIN colleges c
            ON c.collid = d.deptcollid
        WHERE d.deptid = ?
    ");

    $stmt->execute([$deptid]);

    $dept = $stmt->fetch();

} catch (Throwable $e) {
    header('Location: departments.php?msg=db_error');
    exit;

}

if (!$dept) {

    header('Location: departments.php?msg=not_found');
    exit;

}

// Cannot delete if department has existing programs.
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM programs WHERE progcolldeptid = ? AND progid <> 0");
    $stmt->execute([$deptid]);
    $programCount = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    $programCount = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        if ($programCount > 0) {
            $error = 'Cannot delete department record because it is associated with existing programs.';
        } else {
            $stmt = $pdo->prepare("
                DELETE FROM departments
                WHERE deptid = ?
            ");

            $stmt->execute([$deptid]);

            header(
                'Location: departments.php?msg=deleted&collid=' .
                urlencode((string)$dept['deptcollid'])
            );

            exit;
        }

    } catch (Throwable $e) {

        die($e->getMessage());

    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Department - USJ-R SMS</title>
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
        <div class="form-section" style="max-width: 760px;">
            <h2>You are about to delete the following department</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <p style="margin: 8px 0 12px;">
                You are about to delete the following department entry:
            </p>

            <div class="table-wrap" style="margin-bottom: 12px;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 240px;">Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Department ID:</strong></td>
                            <td><?= h($dept['deptid']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Department Full Name:</strong></td>
                            <td><?= h($dept['deptfullname']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Department Short Name:</strong></td>
                            <td><?= h($dept['deptshortname'] ?: '-') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php if ($programCount > 0): ?>
                <div class="alert alert-danger">
                    Cannot delete department record because it is associated with existing programs.
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    Are you sure you want to delete this department entry?
                </div>
                <p style="margin: 10px 0 14px; color:#666;">
                    This entry is part of a high-level relationship in the database.
                    Deleting this entry may affect related data.
                </p>
            <?php endif; ?>

            <form method="POST" action="departmentDelete.php?deptid=<?= urlencode((string)$deptid) ?>" class="form-actions" style="display:flex; gap:8px;">
                <button
                    type="submit"
                    class="btn btn-red"
                    <?= $programCount > 0 ? 'style="opacity:.5; pointer-events:none;"' : '' ?>
                >Yes, Delete Entry</button>
                <a href="departments.php?collid=<?= urlencode((string)($dept['deptcollid'] ?? 0)) ?>" class="btn btn-gray">No, Cancel</a>
            </form>
        </div>
    </main>

    <script src="../../assets/ui.js" defer></script>
</body>
</html>
