<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('update', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');

$collid = (int)($_GET['collid'] ?? 0);
$error = '';
$success = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM colleges WHERE collid = ?");
    $stmt->execute([$collid]);
    $school = $stmt->fetch();
} catch (Throwable $e) {
    $school = null;
}

if (!$school) {
    header('Location: schools.php?msg=db_error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collfullname = trim($_POST['collfullname'] ?? '');
    $collshortname = trim($_POST['collshortname'] ?? '');

    $errors = [];

    if ($collfullname === '') {
        $errors['collfullname'] = 'School Full Name entry cannot be empty';
    } elseif (!isLettersOnly($collfullname)) {
        $errors['collfullname'] = 'School Full Name must contain letters only';
    }
    if ($collshortname === '') {
        $errors['collshortname'] = 'School Short Name entry cannot be empty';
    } elseif (!isLettersOnly($collshortname)) {
        $errors['collshortname'] = 'School Short Name must contain letters only';
    }

    if (!empty($errors)) {

    } elseif ($collfullname === $school['collfullname'] && $collshortname === $school['collshortname']) {
        $error = 'Nothing to update. Original entry matches current entry.';

    } else {
        try {
            $stmt = $pdo->prepare("UPDATE colleges SET collfullname = ?, collshortname = ? WHERE collid = ?");
            $stmt->execute([$collfullname, $collshortname, $collid]);
            $success = 'School entry updated successfully.';
            $stmt = $pdo->prepare("SELECT * FROM colleges WHERE collid = ?");
            $stmt->execute([$collid]);
            $school = $stmt->fetch();
        } catch (Throwable $e) {
            $error = 'Could not update school.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update School - USJ-R SMS</title>
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
        <div style="padding: 8px 0; max-width: 860px;">
            <div class="section-header">
                <h2>School Update</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= h($success) ?></div>
            <?php endif; ?>

            <form id="schoolUpdateForm" method="POST" action="schoolUpdate.php?collid=<?= urlencode((string)$collid) ?>">
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>School ID:</label>
                    <input type="text" id="collid" readonly value="<?= h($school['collid']) ?>">
                    <span class="error-msg"></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>School Full Name:</label>
                    <input type="text" id="collfullname" name="collfullname" value="<?= h($_POST['collfullname'] ?? $school['collfullname']) ?>">
                    <span class="error-msg" id="err-name" style="color:red;"><?= h($errors['collfullname'] ?? '') ?></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>School Short Name:</label>
                    <input type="text" id="collshortname" name="collshortname" value="<?= h($_POST['collshortname'] ?? $school['collshortname']) ?>">
                    <span class="error-msg" style="color:red;"><?= h($errors['collshortname'] ?? '') ?></span>
                </div>

                <div class="form-actions" style="display:flex; gap:8px; margin-top:16px;">
                    <button type="submit" class="btn btn-green">Update School Entry</button>
                    <a href="schoolUpdate.php?collid=<?= urlencode((string)$collid) ?>" class="btn btn-gray">Reset Form</a>
                    <a href="schools.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>
    <script src="../../assets/ui.js" defer></script>
</body>
</html>
