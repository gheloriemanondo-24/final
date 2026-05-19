<?php
require_once __DIR__ . '/database/Service.php';

// Logout must be checked FIRST before anything else
if (($_GET['action'] ?? '') === 'logout') {
    logoutUser();
    header('Location: login.php');
    exit;
}

if (currentUser()) {
    header('Location: screens/homepage.php');
    exit;
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // Debug: Check database connection and user
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if (!$user) {
            $error = "User '$username' not found in database.";
        } else {
            $error = "User found. Status: " . $user['status'] . ", Role: " . $user['role'];
            // Try login
            if (loginUser($username, $password)) {
                header('Location: screens/homepage.php');
                exit;
            }
            $error = "User exists but password mismatch. Stored: '" . $user['password'] . "'";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USJ-R School Management System</title>
    <!-- Note: You will need to link your actual CSS file here -->
    <link rel="stylesheet" href="assets/website.css">
    
</head>
<body>

    <!-- Header equivalent -->
    <header>
        <header class="topbar">
    <a class="topbar-brand" href="login.php">USJ-R School Management System v1.01</a>
    <div class="topbar-right">
        
            <!-- <span class="user-info">You are logged in as: <strong></strong> | 👤</span>
            <form method="POST" action="login.php" style="margin:0">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn-logout">Logout</button>
            </form> -->
        
            <form method="POST" action="login.php" class="login-form">
                <label>Username:</label>
                <input type="text" name="username" value="<?= h($_POST['username'] ?? '') ?>">
                <label>Password:</label>
                <input type="password" name="password">
                <button type="submit" class="btn-login">Login</button>
            </form>
        
    </div>
</header>
    </header>

    <main class="main-content-full">

        <?php if ($error): ?>
            <div class="alert alert-danger" style="max-width: 980px; margin: 0 auto 16px auto;">❌ <?= h($error) ?></div>
        <?php endif; ?>
        
        <!-- Hero Section -->
        <div class="hero">
            <h1>Welcome to USJ-R School Management System</h1>
            <p>Manage your school's operations efficiently</p>
        </div>

        <h2 style="font-size:18px; font-weight:700; margin-bottom:16px;">Quick Access</h2>
        
        <!-- Cards Grid -->
        <div class="cards-grid">
            <div class="card">
                <span class="card-icon">🏫</span>
                <h3>Schools</h3>
                <p>Manage school information and details</p>
            </div>

            <div class="card">
                <span class="card-icon">📚</span>
                <h3>Departments</h3>
                <p>Organize departments within schools</p>
            </div>

            <div class="card">
                <span class="card-icon">🎓</span>
                <h3>Programs</h3>
                <p>Manage academic programs and courses</p>
            </div>

            <div class="card">
                <span class="card-icon">👥</span>
                <h3>Students</h3>
                <p>Manage student records and enrollment</p>
            </div>
        </div>

        <!-- Getting Started Instructions -->
        <div class="getting-started">
            <h3>Getting Started</h3>
            <ol>
                <li>Log in with your credentials</li>
                <li>Navigate to any section using the sidebar menu</li>
                <li>View, create, update, or delete records as needed</li>
                <li>Contact administrator for access requests</li>
            </ol>
        </div>
    </main>

    <!-- Footer equivalent -->
    <footer style="text-align: center; margin-top: 40px; color: #666;">
        <p>&copy; 2026 USJ-R School Management System</p>
    </footer>

</body>
</html>