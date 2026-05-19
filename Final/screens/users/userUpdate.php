<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();

$username = trim($_GET['username'] ?? '');
$error = '';
$success = '';

$hasUserType = tableHasColumn('users', 'usertype');
$hasUserRole = tableHasColumn('users', 'userrole');
$hasRole = tableHasColumn('users', 'role'); // older schema

function normalizeUserRole(string $role): string {
    $role = trim($role);
    if ($role === '') return 'Staff';
    if (strtolower($role) === 'others') return 'Staff';
    return $role;
}

try {
    if ($hasUserType && $hasUserRole) {
        $stmt = $pdo->prepare("SELECT username, usertype, userrole, status FROM users WHERE username = ?");
    } elseif ($hasRole) {
        $stmt = $pdo->prepare("SELECT username, role, status FROM users WHERE username = ?");
    } else {
        $stmt = $pdo->prepare("SELECT username, status FROM users WHERE username = ?");
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
    $usertype = normalizeUserRole((string)($_POST['usertype'] ?? ($_POST['role'] ?? 'Staff')));
    $userrole = normalizeUserRole((string)($_POST['userrole'] ?? ($_POST['role'] ?? 'Staff')));

    $newPassword = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($hasUserType && $hasUserRole) {
        if ($usertype === '' || $userrole === '') {
            $error = 'User Type and User Role are required.';
        }
    } elseif ($hasRole) {
        if ($userrole === '') {
            $error = 'User Role is required.';
        }
    }

    if ($error === '' && $newPassword !== '') {
        if ($confirmPassword === '' || $newPassword !== $confirmPassword) {
            $error = 'Password and Confirm Password must match.';
        }
    }

    if ($error === '') {
        try {
            // Keep existing status (not part of this UI spec)
            $status = (int)($u['status'] ?? 1);

            if ($newPassword !== '') {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                if ($hasUserType && $hasUserRole) {
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, usertype = ?, userrole = ?, status = ? WHERE username = ?");
                    $stmt->execute([$hash, $usertype, $userrole, $status, $username]);
                } elseif ($hasRole) {
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ?, status = ? WHERE username = ?");
                    $stmt->execute([$hash, $userrole, $status, $username]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, status = ? WHERE username = ?");
                    $stmt->execute([$hash, $status, $username]);
                }
            } else {
                if ($hasUserType && $hasUserRole) {
                    $stmt = $pdo->prepare("UPDATE users SET usertype = ?, userrole = ?, status = ? WHERE username = ?");
                    $stmt->execute([$usertype, $userrole, $status, $username]);
                } elseif ($hasRole) {
                    $stmt = $pdo->prepare("UPDATE users SET role = ?, status = ? WHERE username = ?");
                    $stmt->execute([$userrole, $status, $username]);
                } else {
                    // nothing else to update
                }
            }

            header('Location: manageUsers.php?msg=updated');
            exit;
        } catch (Throwable $e) {
            $error = 'Could not update user.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User - USJ-R SMS</title>
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
                <h2>User Update</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <form id="userUpdateForm" method="POST" action="userUpdate.php?username=<?= urlencode((string)$username) ?>">
                <div class="form-actions" style="margin-bottom:12px;">
                    <button type="button" id="btnResetPassword" class="btn btn-gray">Reset Password</button>
                </div>

                <h3 style="margin: 0 0 10px;">User Account Details</h3>
                <div class="table-wrap" style="margin-bottom: 14px;">
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
                                    <td>
                                        <?php $selType = (string)($_POST['usertype'] ?? ($u['usertype'] ?? 'Staff')); ?>
                                        <select name="usertype">
                                            <option value="Administrator" <?= $selType === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                                            <option value="Staff" <?= $selType === 'Staff' ? 'selected' : '' ?>>Staff</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr>
                                <td><strong>User Role:</strong></td>
                                <td>
                                    <?php
                                        $selRole = (string)($_POST['userrole'] ?? $_POST['role'] ?? ($u['userrole'] ?? $u['role'] ?? 'Staff'));
                                        $selRole = normalizeUserRole($selRole);
                                    ?>
                                    <select name="<?= $hasUserRole ? 'userrole' : 'role' ?>">
                                        <option value="Administrator" <?= $selRole === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                                        <option value="Staff" <?= $selRole === 'Staff' ? 'selected' : '' ?>>Staff</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 style="margin: 0 0 10px;">Password Settings</h3>
                <div class="table-wrap" style="margin-bottom: 14px;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 240px;">Field</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>User Password:</strong></td>
                                <td><input type="password" id="password" name="password" value=""></td>
                            </tr>
                            <tr>
                                <td><strong>User Confirm Password:</strong></td>
                                <td><input type="password" id="confirm_password" name="confirm_password" value=""></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="form-actions" style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-green">Update User Settings</button>
                    <a href="userUpdate.php?username=<?= urlencode((string)$username) ?>" class="btn btn-gray">Reset Form</a>
                    <a href="manageUsers.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        // UI-only reset (user can type a new password after clearing).
        document.getElementById('btnResetPassword')?.addEventListener('click', () => {
            const p = document.getElementById('password');
            const c = document.getElementById('confirm_password');
            if (p) p.value = '';
            if (c) c.value = '';
            p?.focus();
        });
    </script>
</body>
</html>