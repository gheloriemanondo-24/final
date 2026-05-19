<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('update', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');

$studid = (int)($_GET['studid'] ?? 0);
$error = '';
$info = '';
$errors = [];

$schools = [];
$departments = [];
$programs = [];

try {
    $schools = $pdo->query("SELECT * FROM colleges ORDER BY collfullname")->fetchAll();
} catch (Throwable $e) { $schools = []; }

try {
    $departments = $pdo->query("SELECT * FROM departments ORDER BY deptfullname")->fetchAll();
} catch (Throwable $e) { $departments = []; }

try {
    $programs = $pdo->query("SELECT * FROM programs ORDER BY progfullname")->fetchAll();
} catch (Throwable $e) { $programs = []; }

try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE studid = ?");
    $stmt->execute([$studid]);
    $stud = $stmt->fetch();
} catch (Throwable $e) {
    $stud = null;
}

if (!$stud) {
    header('Location: students.php?msg=db_error');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studlastname = trim($_POST['studlastname'] ?? '');
    $studfirstname = trim($_POST['studfirstname'] ?? '');
    $studmidname = trim($_POST['studmidname'] ?? '');
    $studcollid = (int)($_POST['studcollid'] ?? 0);
    $studcolldeptid = (int)($_POST['studcolldeptid'] ?? 0);
    $studprogid = (int)($_POST['studprogid'] ?? 0);
    $studyear = (int)($_POST['studyear'] ?? 0);

    // Validate ONLY in PHP (not in HTML attributes)
    $errors = [];
    if ($studfirstname === '' || !isLettersOnly($studfirstname)) $errors['studfirstname'] = 'First Name must contain letters only.';
    if ($studmidname === '' || !isLettersOnly($studmidname)) $errors['studmidname'] = 'Middle Name must contain letters only.';
    if ($studlastname === '' || !isLettersOnly($studlastname)) $errors['studlastname'] = 'Last Name must contain letters only.';
    if ($studcollid === 0) $errors['studcollid'] = 'Please select a School.';
    if ($studcolldeptid === 0) $errors['studcolldeptid'] = 'Please select a Department.';
    if ($studprogid === 0) $errors['studprogid'] = 'Please select a Program.';
    if ($studyear === 0) $errors['studyear'] = 'Please enter Year.';

    if (!empty($errors)) {
        $error = 'Please fix the highlighted fields.';
    } else {
        // If nothing changed, do not run UPDATE and do not show success message.
        $origFirst = trim((string)($stud['studfirstname'] ?? ''));
        $origMid   = trim((string)($stud['studmidname'] ?? ''));
        $origLast  = trim((string)($stud['studlastname'] ?? ''));
        $origColl  = (int)($stud['studcollid'] ?? 0);
        $origDept  = (int)($stud['studcolldeptid'] ?? 0);
        $origProg  = (int)($stud['studprogid'] ?? 0);
        $origYear  = (int)($stud['studyear'] ?? 0);

        $noChanges =
            $studfirstname === $origFirst &&
            $studmidname === $origMid &&
            $studlastname === $origLast &&
            $studcollid === $origColl &&
            $studcolldeptid === $origDept &&
            $studprogid === $origProg &&
            $studyear === $origYear;

        if ($noChanges) {
            $info = 'No changes detected.';
        } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE students
                SET studfirstname = ?, studlastname = ?, studmidname = ?, studcollid = ?, studcolldeptid = ?, studprogid = ?, studyear = ?
                WHERE studid = ?
            ");
            $stmt->execute([$studfirstname, $studlastname, $studmidname, $studcollid, $studcolldeptid, $studprogid, $studyear, $studid]);
            header('Location: students.php?msg=updated');
            exit;
        } catch (Throwable $e) {
            $error = 'Could not update student.';
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
    <title>Update Student - USJ-R SMS</title>
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
                <li><a href="students.php" class="active">Students</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="../users/users.php">Users</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div style="padding: 8px 0; max-width: 980px;">
            <div class="section-header">
                <h2>Student Update</h2>
            </div>

            <div class="alert alert-info">ℹ️ Basic PHP update (updates database).</div>

            <?php if ($info): ?>
                <div class="alert alert-info">ℹ️ <?= h($info) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <form id="studUpdateForm" method="POST" action="studentUpdate.php?studid=<?= urlencode((string)$studid) ?>">
                <div class="form-row">
                    <label>Student ID:</label>
                    <input type="text" id="studid" readonly value="<?= h($stud['studid']) ?>">
                    <span class="error-msg"></span>
                </div>

                <div class="form-row">
                    <label>Last Name:</label>
                    <input type="text" id="studlastname" name="studlastname" value="<?= h($_POST['studlastname'] ?? $stud['studlastname']) ?>">
                    <span class="error-msg" id="err-ln"><?= h($errors['studlastname'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>First Name:</label>
                    <input type="text" id="studfirstname" name="studfirstname" value="<?= h($_POST['studfirstname'] ?? $stud['studfirstname']) ?>">
                    <span class="error-msg" id="err-fn"><?= h($errors['studfirstname'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>Middle Name:</label>
                    <input type="text" id="studmidname" name="studmidname" value="<?= h($_POST['studmidname'] ?? $stud['studmidname']) ?>">
                    <span class="error-msg"><?= h($errors['studmidname'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>School:</label>
                    <?php $selSchool = (string)($_POST['studcollid'] ?? $stud['studcollid']); ?>
                    <select id="studcollid" name="studcollid">
                        <option value="">Select School</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?= h($s['collid']) ?>" <?= (string)$s['collid'] === $selSchool ? 'selected' : '' ?>>
                                <?= h($s['collfullname'] . ' (' . $s['collshortname'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg"><?= h($errors['studcollid'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>Department:</label>
                    <?php $selDept = (string)($_POST['studcolldeptid'] ?? $stud['studcolldeptid']); ?>
                    <select id="studcolldeptid" name="studcolldeptid">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= h($d['deptid']) ?>" data-collid="<?= h($d['deptcollid']) ?>" <?= (string)$d['deptid'] === $selDept ? 'selected' : '' ?>>
                                <?= h($d['deptfullname']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg"><?= h($errors['studcolldeptid'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>Program:</label>
                    <?php $selProg = (string)($_POST['studprogid'] ?? $stud['studprogid']); ?>
                    <select id="studprogid" name="studprogid">
                        <option value="">Select Program</option>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?= h($p['progid']) ?>" data-collid="<?= h($p['progcollid']) ?>" data-deptid="<?= h($p['progcolldeptid']) ?>" <?= (string)$p['progid'] === $selProg ? 'selected' : '' ?>>
                                <?= h(($p['progshortname'] ?: $p['progfullname'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg"><?= h($errors['studprogid'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>Year:</label>
                    <input type="number" id="studyear" name="studyear" min="1" max="6" value="<?= h($_POST['studyear'] ?? $stud['studyear']) ?>">
                    <span class="error-msg" id="err-year"><?= h($errors['studyear'] ?? '') ?></span>
                </div>

                <div class="form-actions" style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-green">Update Student Entry</button>
                    <a href="studentUpdate.php?studid=<?= urlencode((string)$studid) ?>" class="btn btn-gray">Reset Form</a>
                    <a href="students.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>

    
</body>
</html>
