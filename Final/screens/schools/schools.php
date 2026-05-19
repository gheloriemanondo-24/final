<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('view', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');
$canCreate = can('create');
$canUpdate = can('update');
$canDelete = can('delete');
$msg = $_GET['msg'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$total = 0;
$totalPages = 1;
$offset = 0;
$schools = [];
try {
    $total = (int)$pdo->query("SELECT COUNT(*) FROM colleges WHERE collid <> 0")->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT * FROM colleges WHERE collid <> 0 ORDER BY collid LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $schools = $stmt->fetchAll();
} catch (Throwable $e) {
    $msg = 'db_error';
    $schools = [];
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
    <title>School List - USJ-R SMS</title>
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
            <h2>School List</h2>
        </div>

        <?php if ($msg === 'created'): ?>
            <div class="alert alert-success">✅ School entry created successfully.</div>
        <?php elseif ($msg === 'updated'): ?>
            <div class="alert alert-success">✅ School entry updated successfully.</div>
        <?php elseif ($msg === 'deleted'): ?>
            <div class="alert alert-info">🗑️ School entry deleted.</div>
        <?php elseif ($msg === 'db_error'): ?>
            <div class="alert alert-danger">❌ Database error. Import the SQL and check your DB credentials.</div>
        <?php endif; ?>

        <div style="margin-bottom:14px; display:flex; gap:10px;">
            <?php if ($canCreate): ?>
                <a href="schoolCreate.php" class="btn btn-green">➕ Create School Entry</a>
            <?php endif; ?>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>School ID</th>
                        <th>School Full Name</th>
                        <th>School Short Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schools)): ?>
                        <tr><td colspan="4" style="text-align:center;color:#999;padding:24px;">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($schools as $s): ?>
                            <tr>
                                <td><?= h($s['collid']) ?></td>
                                <td><?= h($s['collfullname']) ?></td>
                                <td><?= h($s['collshortname']) ?></td>
                                <td>
                                    <?php if ($canUpdate): ?>
                                        <a href="schoolUpdate.php?collid=<?= urlencode((string)$s['collid']) ?>" class="btn btn-green btn-sm">✏️ Update</a>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                        <a href="schoolDelete.php?collid=<?= urlencode((string)$s['collid']) ?>" class="btn btn-red btn-sm">🗑️ Delete</a>
                                    <?php endif; ?>
                                    <?php if (!$canUpdate && !$canDelete): ?>
                                        <span style="color:#999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-top:12px;">
            <div style="color:#666;">
                Total of: <?= (int)$total ?> schools in the database
            </div>
            
            <div style="display:flex; flex-direction:column; gap:8px; margin-top:12px;">
                <div style="display:flex; gap:8px; justify-content:flex-start;">
                    <?php if ($page > 1): ?>
                        <a class="btn btn-gray btn-sm" href="schools.php?<?= h(http_build_query(['page' => $page - 1])) ?>">Prev</a>
                    <?php else: ?>
                        <span class="btn btn-gray btn-sm" style="opacity:.5; pointer-events:none;">Prev</span>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="btn btn-gray btn-sm" href="schools.php?<?= h(http_build_query(['page' => $page + 1])) ?>">Next</a>
                    <?php else: ?>
                        <span class="btn btn-gray btn-sm" style="opacity:.5; pointer-events:none;">Next</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="../../assets/ui.js" defer></script>
</body>
</html>
