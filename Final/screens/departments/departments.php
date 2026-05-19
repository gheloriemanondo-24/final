<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();
$isAdmin = strtolower($user['role'] ?? '') === 'administrator' 
        || strtolower($user['role'] ?? '') === 'admin';

$msg = $_GET['msg'] ?? '';
$collid = (int)($_GET['collid'] ?? 0); // 0 = all
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$total = 0;
$totalPages = 1;
$offset = 0;

$departments = [];
$schoolLabel = '';

try {
    if ($collid > 0) {
        $stmt = $pdo->prepare("SELECT collfullname, collshortname FROM colleges WHERE collid = ? AND collid <> 0");
        $stmt->execute([$collid]);
        $c = $stmt->fetch();
        if ($c) {
            $schoolLabel = $c['collfullname'] . ' (' . $c['collshortname'] . ')';
        }
    }

    // REPLACE with:
    if ($collid > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE deptid <> 0 AND deptcollid = :collid");
        $stmt->bindValue(':collid', $collid, PDO::PARAM_INT);
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();
    } else {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM departments WHERE deptid <> 0")->fetchColumn();
    }

    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    // REPLACE with:
    if ($collid > 0) {
        $stmt = $pdo->prepare("
            SELECT d.*, c.collfullname, c.collshortname
            FROM departments d
            LEFT JOIN colleges c ON c.collid = d.deptcollid
            WHERE d.deptid <> 0 AND d.deptcollid = :collid
            ORDER BY d.deptid
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':collid', $collid, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $departments = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("
            SELECT d.*, c.collfullname, c.collshortname
            FROM departments d
            LEFT JOIN colleges c ON c.collid = d.deptcollid
            WHERE d.deptid <> 0
            ORDER BY d.deptid
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $departments = $stmt->fetchAll();
    }
} catch (Throwable $e) {
    $dbError = true;
    $departments = [];
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
    <title>Department List - USJ-R School Management System</title>
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
                <li><a href="chooseSchool.php" class="active">Departments</a></li>
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
    <div class="section-header">
        <h2>Department List<?= $collid > 0 && $schoolLabel ? ' - ' . h($schoolLabel) : ' (All Schools)' ?></h2>
    </div>

    <?php if ($msg === 'created'): ?>
        <div class="alert alert-success">✅ Department entry created successfully.</div>
    <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-success">✅ Department entry updated successfully.</div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-info">🗑️ Department entry deleted.</div>
    <?php elseif ($msg === 'db_error' || !empty($dbError)): ?><div class="alert alert-danger">
        ❌ Something went wrong while processing the request.
    </div>
    <?php elseif ($msg === 'not_found'): ?>
        <div class="alert alert-danger">❌ Department not found.</div>
    <?php endif; ?>

    <div style="margin-bottom:14px; display:flex; gap:10px;">
       <a href="departmentCreate.php?collid=<?= $collid ?>" class="btn btn-green">➕ Create Department Entry</a>
        
            <a href="chooseSchool.php" class="btn btn-red">🔙 Back</a>
        
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Department ID</th>
                    <th>Department Full Name</th>
                    <th>Department Short Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;padding:24px;">No records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($departments as $d): ?>
                        <tr>
                            <td><?= h($d['deptid']) ?></td>
                            <td><?= h($d['deptfullname']) ?></td>
                            <td><?= h($d['deptshortname'] ?: '-') ?></td>
                            <td>
                                <a href="departmentUpdate.php?deptid=<?= urlencode((string)$d['deptid']) ?>" class="btn btn-green btn-sm">✏️ Update</a>
                                <a href="departmentDelete.php?deptid=<?= urlencode((string)$d['deptid']) ?>" class="btn btn-red btn-sm">🗑️ Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top:12px; color:#666;">
        Total of: <?= (int)$total ?> departments in the database
    </div>
</main>

</body>
</html>