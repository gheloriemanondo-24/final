<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();
$isAdmin = strtolower($user['role'] ?? '') === 'administrator' 
        || strtolower($user['role'] ?? '') === 'admin';

try {
    $schools = $pdo->query("SELECT * FROM colleges WHERE collid <> 0 ORDER BY collfullname")->fetchAll();
} catch (Throwable $e) {
    $schools = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose School - Departments</title>
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
                <li><a href="departments.php" class="active">Departments</a></li>
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
            <h2>Select School</h2>
        </div>

        <form method="GET" action="departments.php">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <select name="collid" required style="width:300px; height:38px;">
    <option value="">Select School</option>

    <?php foreach ($schools as $s): ?>
        <option value="<?= h($s['collid']) ?>">
            <?= h($s['collfullname']) ?>
        </option>
    <?php endforeach; ?>
</select>
                <button type="submit" class="btn btn-green" style="width:150px; height:38px; display:flex; justify-content:center; align-items:center;">Select Schools</button>
                <a href="departments.php"
   class="btn btn-green"
   style="width:150px; height:38px;
          display:flex;
          justify-content:center;
          align-items:center;
          text-decoration:none;">
    List of Schools
</a>
            </div>
            </div>
        </form>
    </main>
</body>
</html>