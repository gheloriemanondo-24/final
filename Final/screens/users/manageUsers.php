<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('manage_users', '../homepage.php');
$user = currentUser();

$msg = $_GET['msg'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$total = 0;
$totalPages = 1;
$offset = 0;
$users = [];

$hasUserType = tableHasColumn('users', 'usertype');
$hasUserRole = tableHasColumn('users', 'userrole');
$hasRole = tableHasColumn('users', 'role'); // older schema

$colCount = 1 /*#*/ + 1 /*username*/ + 1 /*status*/ + 1 /*actions*/;
if ($hasUserType) $colCount += 1;
if ($hasUserRole || $hasRole) $colCount += 1;

try {
    $total = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    if ($hasUserType && $hasUserRole) {
        $stmt = $pdo->prepare("
            SELECT username, usertype, userrole, status
            FROM users
            ORDER BY username
            LIMIT :limit OFFSET :offset
        ");
    } elseif ($hasRole) {
        $stmt = $pdo->prepare("
            SELECT username, role, status
            FROM users
            ORDER BY username
            LIMIT :limit OFFSET :offset
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT username, status
            FROM users
            ORDER BY username
            LIMIT :limit OFFSET :offset
        ");
    }

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (Throwable $e) {
    $msg = 'db_error';
    $users = [];
    $total = 0;
}

$from = $total === 0 ? 0 : ($offset + 1);
$to = min($offset + $perPage, $total);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - USJ-R SMS</title>
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
        <div class="section-header">
            <h2>Manage Users</h2>
        </div>

        <?php if ($msg === 'updated'): ?>
            <div class="alert alert-success">✅ User updated successfully.</div>
        <?php elseif ($msg === 'deleted'): ?>
            <div class="alert alert-info">🗑️ User deleted.</div>
        <?php elseif ($msg === 'db_error'): ?>
            <div class="alert alert-danger">❌ Database error. Import the SQL and check your DB credentials.</div>
        <?php endif; ?>

        <div style="margin-bottom:14px; display:flex; gap:10px;">
            <a href="users.php" class="btn btn-gray">Back to Dashboard</a>
            <a href="../homepage.php" class="btn btn-red">Exit</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User Name</th>
                        <?php if ($hasUserType): ?><th>User Type</th><?php endif; ?>
                        <?php if ($hasUserRole || $hasRole): ?><th>User Role</th><?php endif; ?>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="<?= (int)$colCount ?>" style="text-align:center;color:#999;padding:24px;">No records found.</td></tr>
                    <?php else: ?>
                        <?php $i = $offset; foreach ($users as $u): $i++; ?>
                            <tr>
                                <td><?= (int)$i ?></td>
                                <td><?= h($u['username'] ?? '') ?></td>
                                <?php if ($hasUserType): ?>
                                    <?php
                                        $typeVal = (string)($u['usertype'] ?? 'Creator');
                                        $canonType = normalizeRole($typeVal);
                                        $typeLabel = match ($canonType) {
                                            'administrator' => 'Administrator',
                                            'creator'       => 'Creator',
                                            'viewer'        => 'Viewer',
                                            'updater'       => 'Updater',
                                            'remover'       => 'Remover',
                                            default         => 'Creator',
                                        };
                                    ?>
                                    <td><?= h($typeLabel) ?></td>
                                <?php endif; ?>
                                <?php if ($hasUserRole || $hasRole): ?>
                                    <td>
                                        <?php
                                            $roleVal = (string)($u['userrole'] ?? $u['role'] ?? 'Creator');
                                            $canon = normalizeRole($roleVal);
                                            $label = match ($canon) {
                                                'administrator' => 'Administrator',
                                                'creator'       => 'Creator',
                                                'viewer'        => 'Viewer',
                                                'updater'       => 'Updater',
                                                'remover'       => 'Remover',
                                                default         => 'Creator',
                                            };
                                            echo h($label);
                                        ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php if ((int)($u['status'] ?? 1) === 1): ?>
                                        <span style="color:#28a745;font-weight:600;">● Active</span>
                                    <?php else: ?>
                                        <span style="color:#dc3545;font-weight:600;">● Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="userUpdate.php?username=<?= urlencode((string)($u['username'] ?? '')) ?>" class="btn btn-green btn-sm">⚙️ Settings</a>
                                    <a href="userDelete.php?username=<?= urlencode((string)($u['username'] ?? '')) ?>" class="btn btn-red btn-sm">🗑️ Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-top:12px;">
            <div style="color:#666;">
                Showing <?= (int)$from ?>–<?= (int)$to ?> of <?= (int)$total ?>
            </div>
            <div style="display:flex; gap:8px;">
                <?php if ($page > 1): ?>
                    <a class="btn btn-gray btn-sm" href="manageUsers.php?<?= h(http_build_query(['page' => $page - 1])) ?>">Prev</a>
                <?php else: ?>
                    <span class="btn btn-gray btn-sm" style="opacity:.5; pointer-events:none;">Prev</span>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="btn btn-gray btn-sm" href="manageUsers.php?<?= h(http_build_query(['page' => $page + 1])) ?>">Next</a>
                <?php else: ?>
                    <span class="btn btn-gray btn-sm" style="opacity:.5; pointer-events:none;">Next</span>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="../../assets/ui.js" defer></script>
</body>
</html>
