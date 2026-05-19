<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('create', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');


$error = '';
$success = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collidRaw = (string)($_POST['collid'] ?? '');
    $collfullname = trim((string)($_POST['collfullname'] ?? ''));
    $collshortname = trim((string)($_POST['collshortname'] ?? ''));
    $errors = [];

    // Validate in PHP (not HTML)
    if (trim($collidRaw) === '') {
        $errors['collid'] = 'School ID entry cannot be empty';
    } elseif (!isDigitsOnly($collidRaw)) {
        $errors['collid'] = 'School ID must contain numbers only';
    }
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

    if (empty($errors)) {
        try {
            $collid = (int)$collidRaw;
            $stmt = $pdo->prepare("INSERT INTO colleges (collid, collfullname, collshortname) VALUES (?, ?, ?)");
            $stmt->execute([$collid, $collfullname, $collshortname]);
            // PRG: avoid refilling the form after successful create
            header('Location: schools.php?msg=created');
            exit;
        } catch (Throwable $e) {
            $error = 'Could not create school. (Maybe duplicate ID?)';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create School - USJ-R SMS</title>
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
                <h2>School Create</h2>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= h($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <form id="schoolCreateForm" method="POST" action="schoolCreate.php">
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>School ID:</label>
                    <input type="text" id="collid" name="collid" value="<?= h($_POST['collid'] ?? '') ?>">
                    <?php if (!empty($errors['collid'])): ?>
                        <span class="error-msg" id="err-id" style="color:red;"><?= h($errors['collid'] ?? '') ?></span>
                    <?php else: ?>
                        <span class="error-msg" id="err-id" style="color:#666;">Format: numbers only (example: 11)</span>
                    <?php endif; ?>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>School Full Name:</label>
                    <input type="text" id="collfullname" name="collfullname" value="<?= h($_POST['collfullname'] ?? '') ?>">
                    <span class="error-msg" id="err-name" style="color:red;"><?= h($errors['collfullname'] ?? '') ?></span>
                </div>

                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>School Short Name:</label>
                    <input type="text" id="collshortname" name="collshortname" value="<?= h($_POST['collshortname'] ?? '') ?>">
                    <span class="error-msg" style="color:red;"><?= h($errors['collshortname'] ?? '') ?></span>
                </div>

                <div class="form-actions" style="display:flex; gap:8px; margin-top:16px;">
                    <button type="submit" class="btn btn-green">Save New School Entry</button>
                    <a href="schoolCreate.php" class="btn btn-gray">Reset Form</a>
                    <a href="schools.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
