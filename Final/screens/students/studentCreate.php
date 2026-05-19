<?php
require_once __DIR__ . '/../../database/Service.php';
requireLogin('../../login.php');
requireCapability('create', '../homepage.php');
$user = currentUser();
$isAdmin = can('manage_users');

$error = '';
$errors = [];
$schools = [];
$departments = [];
$programs = [];

try {
    $schools = $pdo->query("SELECT * FROM colleges ORDER BY collfullname")->fetchAll();
} catch (Throwable $e) {
    $schools = [];
}

try {
    $departments = $pdo->query("SELECT * FROM departments ORDER BY deptfullname")->fetchAll();
} catch (Throwable $e) {
    $departments = [];
}

try {
    $programs = $pdo->query("SELECT * FROM programs ORDER BY progfullname")->fetchAll();
} catch (Throwable $e) {
    $programs = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // NOTE: Student ID is a 10-digit identifier. We keep it as a string first
    // to preserve leading zeros (if any) and validate properly before casting.
    $studidRaw = trim((string)($_POST['studid'] ?? ''));
    $studid = (int)$studidRaw;
    $studlastname = trim($_POST['studlastname'] ?? '');
    $studfirstname = trim($_POST['studfirstname'] ?? '');
    $studmidname = trim($_POST['studmidname'] ?? '');
    $studcollid = (int)($_POST['studcollid'] ?? 0);
    $studcolldeptid = (int)($_POST['studcolldeptid'] ?? 0);
    $studprogid = (int)($_POST['studprogid'] ?? 0);
    $studyear = (int)($_POST['studyear'] ?? 0);

    $studidStr = $studidRaw;
    $deptStr = $studcolldeptid > 0 ? str_pad((string)$studcolldeptid, 5, '0', STR_PAD_LEFT) : '';

    // Validate ONLY in PHP (not in HTML attributes)
    $errors = [];

    if (trim($studidStr) === '') {
        $errors['studid'] = 'Student ID entry cannot be empty';
    } elseif (!preg_match('/^\d+$/', $studidStr)) {
        $errors['studid'] = 'Student ID must contain numbers only';
    } elseif (!preg_match('/^\d{10}$/', $studidStr)) {
        $errors['studid'] = 'Student ID must be exactly 10 digits';
    } elseif ((int)$studidStr > 2147483647) {
        // In the provided SQL, students.studid is INT (max 2147483647).
        // IDs higher than this will fail to insert.
        $errors['studid'] = 'Student ID is too large for the database. Please follow the suggested format (e.g. 21 + deptid + 001 like 2111001001).';
    }

    if ($studfirstname === '' || !isLettersOnly($studfirstname)) {
        $errors['studfirstname'] = 'First Name must contain letters only.';
    }
    if ($studmidname === '' || !isLettersOnly($studmidname)) {
        $errors['studmidname'] = 'Middle Name must contain letters only.';
    }
    if ($studlastname === '' || !isLettersOnly($studlastname)) {
        $errors['studlastname'] = 'Last Name must contain letters only.';
    }

    if ($studcollid === 0) $errors['studcollid'] = 'Please select a School.';
    if ($studcolldeptid === 0) $errors['studcolldeptid'] = 'Please select a Department.';
    if ($studprogid === 0) $errors['studprogid'] = 'Please select a Program.';
    if ($studyear === 0) $errors['studyear'] = 'Please enter Year.';

    if (!empty($errors)) {
        // Keep a simple top message for visibility, while also showing per-field errors.
        $error = 'Please fix the highlighted fields.';
    } else {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO students
            (studid, studfirstname, studlastname, studmidname, studcollid, studcolldeptid, studprogid, studyear)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $studid,
            $studfirstname,
            $studlastname,
            $studmidname,
            $studcollid,
            $studcolldeptid,
            $studprogid,
            $studyear
        ]);

        // After creating, redirect back to Student List with the selected
        // school/department/program auto-applied as filters.
        $redirectParams = [
            'msg'    => 'created',
            'collid' => $studcollid,
            'deptid' => $studcolldeptid,
            'progid' => $studprogid,
        ];
        header('Location: students.php?' . http_build_query($redirectParams));
        exit;

    } catch (Throwable $e) {
        // Most common reasons:
        // - duplicate primary key (same Student ID already exists)
        // - out-of-range ID for INT column (handled above, but keep message friendly)
        $error = 'Could not create student. Student ID may already exist.';
    }
}
}
?>
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student - USJ-R SMS</title>
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
                <h2>Student Create</h2>
            </div>

            <div class="alert alert-info">ℹ️ Basic PHP create (inserts into database).</div>

            <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?= h($error) ?></div>
            <?php endif; ?>

            <form id="studCreateForm" method="POST" action="studentCreate.php">
                <div class="form-row">
                    <label>Student ID:</label>
                    <input type="text" id="studid" name="studid" value="<?= h($_POST['studid'] ?? '') ?>">
                    <?php if (!empty($errors['studid'])): ?>
                        <span class="error-msg" id="err-id"><?= h($errors['studid'] ?? '') ?></span>
                    <?php else: ?>
                        <span class="error-msg" id="err-id" style="color:#666;">Format: numbers only, exactly 10 digits (example: 2111001001)</span>
                    <?php endif; ?>
                </div>
                <p class="total-row" style="margin-top:-6px;">
                    Example: <strong>21: SOFA</strong> | <strong>21001: DOFA</strong> | <strong>2121001001</strong>
                </p>

                <div class="form-row">
                    <label>Student First Name:</label>
                    <input type="text" id="studfirstname" name="studfirstname" value="<?= h($_POST['studfirstname'] ?? '') ?>">
                    <span class="error-msg" id="err-fn"><?= h($errors['studfirstname'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>Student Middle Name:</label>
                    <input type="text" id="studmidname" name="studmidname" value="<?= h($_POST['studmidname'] ?? '') ?>">
                    <span class="error-msg"><?= h($errors['studmidname'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>Student Last Name:</label>
                    <input type="text" id="studlastname" name="studlastname" value="<?= h($_POST['studlastname'] ?? '') ?>">
                    <span class="error-msg" id="err-ln"><?= h($errors['studlastname'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>School:</label>
                    <select id="studcollid" name="studcollid">
                        <option value="">Select School</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?= h($s['collid']) ?>" <?= (string)$s['collid'] === (string)($_POST['studcollid'] ?? '') ? 'selected' : '' ?>>
                                <?= h($s['collfullname'] . ' (' . $s['collshortname'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg" id="err-school"><?= h($errors['studcollid'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>Department:</label>
                    <select id="studcolldeptid" name="studcolldeptid">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= h($d['deptid']) ?>" data-collid="<?= h($d['deptcollid']) ?>" <?= (string)$d['deptid'] === (string)($_POST['studcolldeptid'] ?? '') ? 'selected' : '' ?>>
                                <?= h($d['deptfullname']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg" id="err-dept"><?= h($errors['studcolldeptid'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>Program:</label>
                    <select id="studprogid" name="studprogid">
                        <option value="">Select Program</option>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?= h($p['progid']) ?>" data-collid="<?= h($p['progcollid']) ?>" data-deptid="<?= h($p['progcolldeptid']) ?>" <?= (string)$p['progid'] === (string)($_POST['studprogid'] ?? '') ? 'selected' : '' ?>>
                                <?= h(($p['progshortname'] ?: $p['progfullname'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error-msg" id="err-prog"><?= h($errors['studprogid'] ?? '') ?></span>
                </div>

                <div class="form-row">
                    <label>Year:</label>
                    <input type="number" id="studyear" name="studyear" min="1" max="6" placeholder="1-6" value="<?= h($_POST['studyear'] ?? '') ?>" required>
                    <span class="error-msg" id="err-year"><?= h($errors['studyear'] ?? '') ?></span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-green">Save New Student Entry</button>
                    <a href="studentCreate.php" class="btn btn-gray">Reset Form</a>
                    <a href="students.php" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        const schoolSelect = document.getElementById('studcollid');
        const deptSelect = document.getElementById('studcolldeptid');
        const progSelect = document.getElementById('studprogid');
        const studIdInput = document.getElementById('studid');

        function filterDeptOptions() {
            const collid = schoolSelect.value;
            const opts = Array.from(deptSelect.querySelectorAll('option[data-collid]'));
            opts.forEach(o => o.hidden = (collid && o.dataset.collid !== collid));
            if (deptSelect.selectedOptions[0]?.hidden) deptSelect.value = '';
        }

        function filterProgramOptions() {
            const collid = schoolSelect.value;
            const selectedDept = deptSelect.value;
            const opts = Array.from(progSelect.querySelectorAll('option[data-collid]'));
            opts.forEach(o => {
                if (!collid) { o.hidden = false; return; }
                const matchSchool = o.dataset.collid === collid;
                const matchDept = !selectedDept || o.dataset.deptid === selectedDept;
                o.hidden = !(matchSchool && matchDept);
            });
            if (progSelect.selectedOptions[0]?.hidden) progSelect.value = '';
        }

        function onSchoolChange() {
            filterDeptOptions();
            filterProgramOptions();
        }

        schoolSelect.addEventListener('change', onSchoolChange);
        deptSelect.addEventListener('change', () => {
            filterProgramOptions();
            // If user hasn't typed an ID yet, show a suggested format: 21 + deptid + 001
            const dept = deptSelect.value;
            if (dept && !studIdInput.value) {
                studIdInput.placeholder = `e.g. 21${dept}001`;
            }
        });
        onSchoolChange();
    </script>
</body>
</html>
