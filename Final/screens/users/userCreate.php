<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();

$error = '';
$hasUserType = tableHasColumn('users', 'usertype');
$hasUserRole = tableHasColumn('users', 'userrole');
$hasRole = tableHasColumn('users', 'role'); // older schema

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $usertype = trim((string)($_POST['usertype'] ?? 'Staff'));
    $userrole = trim((string)($_POST['userrole'] ?? $_POST['role'] ?? 'Staff'));

    if ($username === '' || $password === '') {
        $error = 'Username and Password are required.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($hasUserType && $hasUserRole) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, usertype, userrole, status) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$username, $hash, $usertype, $userrole]);
            } elseif ($hasRole) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 1)");
                $stmt->execute([$username, $hash, $userrole]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, status) VALUES (?, ?, 1)");
                $stmt->execute([$username, $hash]);
            }
            header('Location: users.php?msg=created');
            exit;
        } catch (Throwable $e) {
            $error = 'Could not create user. (Maybe duplicate username?)';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - USJ-R SMS</title>
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
                <li><a href="../programs/programs.php">Programs</a></li>
                <li><a href="../students/students.php">Students</a></li>
                <li><a href="users.php" class="active">Users</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div style="padding: 8px 0; max-width: 860px;">
            <div class="section-header">
                <h2>Create User</h2>
            </div>

            <div class="alert alert-info">ℹ️ Basic PHP create (inserts into database).</div>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <form id="userCreateForm" method="POST" action="userCreate.php">
                <div class="form-row">
                    <label>Username:</label>
                    <input type="text" id="username" name="username" placeholder="e.g. staff2" value="<?= h($_POST['username'] ?? '') ?>">
                    <span class="error-msg" id="err-username"></span>
                </div>

                <div class="form-row">
                    <label>Password:</label>
                    <input type="password" id="password" name="password" placeholder="e.g. ********">
                    <span class="error-msg" id="err-password"></span>
                </div>

                <?php if ($hasUserType): ?>
                    <div class="form-row">
                        <label>User Type:</label>
                        <?php $selType = (string)($_POST['usertype'] ?? 'Staff'); ?>
                        <select id="usertype" name="usertype">
                            <option value="Administrator" <?= $selType === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                            <option value="Staff" <?= $selType === 'Staff' ? 'selected' : '' ?>>Staff</option>
                        </select>
                        <span class="error-msg"></span>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <label>User Role:</label>
                    <?php $selRole = (string)($_POST['userrole'] ?? $_POST['role'] ?? 'Staff'); ?>
                    <select id="<?= $hasUserRole ? 'userrole' : 'role' ?>" name="<?= $hasUserRole ? 'userrole' : 'role' ?>">
                        <option value="Administrator" <?= $selRole === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                        <option value="Staff" <?= $selRole === 'Staff' ? 'selected' : '' ?>>Staff</option>
                    </select>
                    <span class="error-msg" id="err-role"></span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-green">Save New User</button>
                    <a href="userCreate.php" class="btn btn-gray">Reset Form</a>
                    <a href="users.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>