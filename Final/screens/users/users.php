<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();

// Only administrators can manage users
requireCapability('manage_users', '../homepage.php');
$isAdmin = true;
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - USJ-R SMS</title>
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
                <li><a href="../students/students.php">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../users/users.php">Users</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="section-header">
            <h2>User Dashboard</h2>
        </div>

        <?php if ($msg === 'import_done'): ?>
            <div class="alert alert-success">✅ File processed. Check the messages on the import page for details.</div>
        <?php endif; ?>

        <div class="form-section" style="max-width: 760px;">
            <div class="alert alert-info" style="margin-bottom:14px;">
                Choose what you want to do:
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="manageUsers.php" class="btn btn-green">Manage Users</a>
                <a href="userImport.php" class="btn btn-gray">Add Users</a>
                <a href="../homepage.php" class="btn btn-red">Exit</a>
            </div>
        </div>
    </main>
</body>
</html>
