

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USJ-R School Management System</title>
    <!-- Note: You will need to link your actual CSS file here -->
    <link rel="stylesheet" href="login.css"> 
    
</head>
<body>

    <!-- Header equivalent -->
    <header>
        <header class="topbar">
    <a class="topbar-brand" href="<?= $basePath ?>LogIn.php">USJ-R School Management System v1.01</a>
    <div class="topbar-right">
        
            <!-- <span class="user-info">You are logged in as: <strong></strong> | 👤</span>
            <form method="POST" action="<?= $basePath ?>LogIn.php" style="margin:0">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn-logout">Logout</button>
            </form> -->
        
            <form method="POST" action="<?= $basePath ?>LogIn.php" class="login-form">
                <input type="hidden" name="action" value="login">
                <label>Username:</label>
                <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                <label>Password:</label>
                <input type="password" name="password">
                <button type="submit" class="btn-login">Login</button>
            </form>
        
    </div>
</header>
    </header>

    <main class="main-content-full">
        
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