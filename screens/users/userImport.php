<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
$user = currentUser();

$hasUserType = tableHasColumn('users', 'usertype');
$hasUserRole = tableHasColumn('users', 'userrole');
$hasRole = tableHasColumn('users', 'role'); // older schema

$error = '';
$messages = [];
$hadErrors = false;

function normalizeRole(string $role): string {
    $role = trim($role);
    if ($role === '') return 'Staff';
    if (strtolower($role) === 'others') return 'Staff';
    return $role;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['csvfile']) || (int)($_FILES['csvfile']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'Please choose a CSV file to upload.';
    } else {
        $tmp = (string)($_FILES['csvfile']['tmp_name'] ?? '');
        $fh = @fopen($tmp, 'r');
        if (!$fh) {
            $error = 'Could not read uploaded file.';
        } else {
            // PHP 8.4+ deprecation: explicitly pass $escape (default will change in future)
            $firstRow = fgetcsv($fh, 0, ",", "\"", "\\");
            if (!$firstRow) {
                $error = 'The CSV file is empty.';
            } else {
                // Determine if header row exists
                $lower = array_map(fn($v) => strtolower(trim((string)$v)), $firstRow);
                $hasHeader = in_array('username', $lower, true);
                $header = $hasHeader ? $lower : ['username', 'password', 'usertype', 'userrole'];

                if (!$hasHeader) {
                    // treat first row as data
                    rewind($fh);
                }

                // Build header index map
                $idx = [];
                foreach ($header as $i => $name) {
                    $idx[$name] = $i;
                }

                $stmtExists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");

                // Insert statement depends on schema
                if ($hasUserType && $hasUserRole) {
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO users (username, password, usertype, userrole, status)
                        VALUES (?, ?, ?, ?, 1)
                    ");
                } elseif ($hasRole) {
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO users (username, password, role, status)
                        VALUES (?, ?, ?, 1)
                    ");
                } else {
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO users (username, password, status)
                        VALUES (?, ?, 1)
                    ");
                }

                while (($row = fgetcsv($fh, 0, ",", "\"", "\\")) !== false) {
                    $username = trim((string)($row[$idx['username'] ?? 0] ?? ''));
                    $password = (string)($row[$idx['password'] ?? 1] ?? '');
                    $usertype = normalizeRole((string)($row[$idx['usertype'] ?? 2] ?? 'Staff'));
                    $userrole = normalizeRole((string)($row[$idx['userrole'] ?? 3] ?? $row[$idx['role'] ?? 3] ?? 'Staff'));

                    if ($username === '') continue;

                    // Duplicate check
                    $stmtExists->execute([$username]);
                    $exists = (int)$stmtExists->fetchColumn() > 0;
                    if ($exists) {
                        $hadErrors = true;
                        $messages[] = "User already exists: {$username}. Skipping insertion.";
                        continue;
                    }

                    if (trim($password) === '') {
                        $hadErrors = true;
                        $messages[] = "Missing password for: {$username}. Skipping insertion.";
                        continue;
                    }

                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    try {
                        if ($hasUserType && $hasUserRole) {
                            $stmtInsert->execute([$username, $hash, $usertype, $userrole]);
                        } elseif ($hasRole) {
                            $stmtInsert->execute([$username, $hash, $userrole]);
                        } else {
                            $stmtInsert->execute([$username, $hash]);
                        }
                    } catch (Throwable $e) {
                        $hadErrors = true;
                        $messages[] = "Could not insert user: {$username}.";
                    }
                }

                fclose($fh);

                if (!$error) {
                    if ($hadErrors) {
                        array_unshift($messages, 'File processed with some errors. Please review the error messages.');
                    } else {
                        $messages[] = '✅ File processed successfully.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Users - USJ-R SMS</title>
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
        <div class="section-header">
            <h2>Add Users From File</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= h($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $m): ?>
                <?php if (str_starts_with($m, '✅')): ?>
                    <div class="alert alert-success"><?= h($m) ?></div>
                <?php elseif (str_starts_with($m, 'File processed with some errors')): ?>
                    <div class="alert alert-danger"><?= h($m) ?></div>
                <?php else: ?>
                    <div class="alert alert-danger"><?= h($m) ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="form-section" style="max-width: 760px;">
            <form method="POST" action="userImport.php" enctype="multipart/form-data">
                <div class="form-row" style="display:flex; gap:10px; align-items:center;">
                    <label style="min-width: 180px;">Choose File:</label>
                    <input type="file" name="csvfile" accept=".csv,text/csv">
                    <span style="color:#666;">Select a CSV file to upload</span>
                </div>

                <div class="form-actions" style="display:flex; gap:8px; margin-top:16px;">
                    <button type="submit" class="btn btn-green">Upload</button>
                    <a href="users.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>