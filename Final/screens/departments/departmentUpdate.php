<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('update', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');

$deptid = (int)($_GET['deptid'] ?? 0);
$error = '';
$schools = [];

try {
    $schools = $pdo->query("SELECT * FROM colleges ORDER BY collfullname")->fetchAll();
} catch (Throwable $e) {
    $schools = [];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE deptid = ?");
    $stmt->execute([$deptid]);
    $dept = $stmt->fetch();
} catch (Throwable $e) {
    $dept = null;
}

if (!$dept) {
    header('Location: departments.php?msg=db_error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deptfullname = trim($_POST['deptfullname'] ?? '');
    $deptshortname = trim($_POST['deptshortname'] ?? '');
    $deptcollid = (int)($_POST['deptcollid'] ?? 0);

   if ($deptfullname === '' || $deptcollid === 0) {
    $error = 'Department Full Name and School are required.';
    } elseif (
        $deptfullname === $dept['deptfullname'] &&
        $deptshortname === $dept['deptshortname'] &&
        $deptcollid === (int)$dept['deptcollid']
    ) {
        $error = 'Nothing to update. Original entry matches current entry.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE departments SET deptfullname = ?, deptshortname = ?, deptcollid = ? WHERE deptid = ?");
            $stmt->execute([$deptfullname, $deptshortname, $deptcollid, $deptid]);
            header('Location: departments.php?msg=updated&collid=' . urlencode((string)$deptcollid));
            exit;
        } catch (Throwable $e) {
            $error = 'Could not update department.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Department - USJ-R SMS</title>
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
        <div style="padding: 8px 0; max-width: 860px;">
            <div class="section-header">
                <h2>Department Update</h2>
            </div>


  
            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <form id="deptUpdateForm" method="POST" action="departmentUpdate.php?deptid=<?= urlencode((string)$deptid) ?>">
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Department ID:</label>
                    <input type="text" id="deptid" readonly value="<?= h($dept['deptid']) ?>">
                    <span class="error-msg"></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Department Full Name:</label>
                    <input type="text" id="deptfullname" name="deptfullname" placeholder="e.g. Department of Computer Studies" value="<?= h($_POST['deptfullname'] ?? $dept['deptfullname']) ?>">
                    <span class="error-msg" id="err-name"></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Department Short Name:</label>
                    <input type="text" id="deptshortname" name="deptshortname" placeholder="e.g. DCS" value="<?= h($_POST['deptshortname'] ?? $dept['deptshortname']) ?>">
                    <span class="error-msg"></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>School:</label>
                    <select id="deptcollid" name="deptcollid">
                        <option value="">Select School</option>
                        <?php
                            $selected = (string)($_POST['deptcollid'] ?? $dept['deptcollid']);
                        ?>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?= h($s['collid']) ?>" <?= (string)$s['collid'] === $selected ? 'selected' : '' ?>>
                                <?= h($s['collfullname'] . ' (' . $s['collshortname'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg"></span>
                </div>

                <div class="form-actions" style="display:flex; gap:8px; margin-top:16px;">
                    <button type="submit" class="btn btn-green">Update Department Entry</button>
                    <a href="departmentUpdate.php?deptid=<?= urlencode((string)$deptid) ?>" class="btn btn-gray">Reset Form</a>
                    <a href="departments.php?collid=<?= (int)$dept['deptcollid'] ?>" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
