<?php
require_once __DIR__ . '/../database/Service.php';
requireLogin('../login.php');
$user = currentUser();
$isAdmin = can('manage_users');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USJR School Management System</title>
    <link rel="stylesheet" href="homepage.css">
</head>
<body>
    <header class="topbar">
        <a class="topbar-brand" href="#">USJ-R School Management System v1.01</a>
        <div class="topbar-right">
            <span class="user-info">You are logged in as: <strong><?= h($user['username'] ?? '') ?></strong> | 👤</span>
            <a href="../login.php?action=logout" class="btn-logout" style="text-decoration:none;">Logout</a>
        </div>
    </header>

    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="homepage.php" class="active">Home</a></li>
                <li><a href="schools/schools.php">Schools</a></li>
                <li><a href="departments/chooseSchool.php">Departments</a></li>
                <li><a href="programs/programs.php">Programs</a></li>
                <li><a href="students/students.php">Students</a></li>
                <?php if ($isAdmin): ?>
                <li><a href="users/users.php">Users</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="hero">
            <h1>Welcome to USJ-R School Management System</h1>
            <p>Hello, <strong><?= h($user['username'] ?? '') ?>!</strong> 👋</p>
            <p>Manage your school's operations efficiently</p>
        </div>

        <h2 style="font-size:18px; font-weight:700; padding-bottom:8px; border-bottom:3px solid var(--green); margin-bottom:20px;">
            Quick Access
        </h2>

        <div class="cards-grid">
            <div class="card">
                <span class="card-icon">🏫</span>
                <h3>Schools</h3>
                <p>Manage school information and details</p>
                <a href="schools/schools.php" class="btn">View Schools</a>
            </div>
            <div class="card">
                <span class="card-icon">📚</span>
                <h3>Departments</h3>
                <p>Organize departments within schools</p>
                <a href="departments/chooseSchool.php" class="btn">View Departments</a>
            </div>
            <div class="card">
                <span class="card-icon">🎓</span>
                <h3>Programs</h3>
                <p>Manage academic programs and courses</p>
                <a href="programs/programs.php" class="btn">View Programs</a>
            </div>
            <div class="card">
                <span class="card-icon">👥</span>
                <h3>Students</h3>
                <p>Manage student records and enrollment</p>
                <a href="students/students.php" class="btn">View Students</a>
            </div>
            <?php if ($isAdmin): ?>
            <div class="card" style="border-color: orange;">
                <span class="card-icon">⚙️</span>
                <h3>User Management</h3>
                <p>Manage system users and permissions</p>
                <a href="users/users.php" class="btn" style="background:orange;">Manage Users</a>
            </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
