<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('manage_users', '../homepage.php');
$user = currentUser();

$username = trim($_GET['username'] ?? '');
$error = '';

$hasUserType = tableHasColumn('users', 'usertype');
$hasUserRole = tableHasColumn('users', 'userrole');
$hasRole = tableHasColumn('users', 'role'); // older schema

try {
    if ($hasUserType && $hasUserRole) {
        $stmt = $pdo->prepare("SELECT username, usertype, userrole FROM users WHERE username = ?");
    } elseif ($hasRole) {
        $stmt = $pdo->prepare("SELECT username, role FROM users WHERE username = ?");
    } else {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
    }
    $stmt->execute([$username]);
    $u = $stmt->fetch();
} catch (Throwable $e) {
    $u = null;
}

if (!$u) {
    header('Location: manageUsers.php?msg=db_error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($user['username'] ?? '') === $username) {
        $error = "You can't delete the currently logged-in user.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
            $stmt->execute([$username]);
            header('Location: manageUsers.php?msg=deleted');
            exit;
        } catch (Throwable $e) {
            $error = 'Cannot delete this user.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - USJ-R SMS</title>
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
                <li><a href="users.php" class="active">Users</a></li>
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
                ⚠️ Are you sure you want to delete this user entry?
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
                            <td><strong>User Name:</strong></td>
                            <td><?= h($u['username'] ?? '') ?></td>
                        </tr>
                        <?php if ($hasUserType): ?>
                            <tr>
                                <td><strong>User Type:</strong></td>
                                <td><?= h($u['usertype'] ?? 'Creator') ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($hasUserRole || $hasRole): ?>
                            <tr>
                                <td><strong>User Role:</strong></td>
                                <td><?= h($u['userrole'] ?? $u['role'] ?? 'Creator') ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <form method="POST" action="userDelete.php?username=<?= urlencode((string)$username) ?>" class="form-actions">
                <a href="manageUsers.php" class="btn btn-gray">No, Cancel</a>
                <button type="submit" class="btn btn-red">Yes, Delete Entry</button>
            </form>
        </div>
    </main>
    <script src="../../assets/ui.js" defer></script>
</body>
</html>
