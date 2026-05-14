<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USJR School Management System</title>
    <link rel="stylesheet" href="homepage.css">
</head>
<body>

    <header class="topbar">
        <a class="topbar-brand" href="#">USJ-R School Management System v1.01</a>
        <div class="topbar-right">
            <span class="user-info">You are logged in as: <strong>AdminUser</strong> | 👤</span>
            <button class="btn" style="background:var(--red); margin:0;">Logout</button>
        </div>
    </header>

    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="departments/departments.php" class="active">Home</a></li>
                <li><a href="schools/schools.php">Schools</a></li>
                <li><a href="departments/departments.php">Departments</a></li>
                <li><a href="programs/programs.php">Programs</a></li>
                <li><a href="students/students.php">Students</a></li>
                <li><a href="users/users.php">Users</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="hero">
            <h1>Welcome to USJ-R School Management System</h1>
            <p>Hello, <strong>AdminUser!</strong> 👋</p>
            <p>Manage your school's operations efficiently</p>
        </div>

        <h2 style="font-size:18px; font-weight:700; padding-bottom:8px; border-bottom:3px solid var(--green); margin-bottom:20px;">
            Quick Access
        </h2>

        <div class="cards-grid">
            <div class="card">
                <span class="card-icon">🏫</span>
                <h3>Schools</h3>
                <p>Manage school records</p>
                <a href="#schools/schools.php" class="btn">View Schools</a>
            </div>

            <div class="card">
                <span class="card-icon">📚</span>
                <h3>Departments</h3>
                <p>Organize departments</p>
                <a href="#departments/departments.php" class="btn">View Depts</a>
            </div>

            <div class="card">
                <span class="card-icon">🎓</span>
                <h3>Programs</h3>
                <p>Manage courses</p>
                <a href="#programs/programs.php" class="btn">View Programs</a>
            </div>

            <div class="card">
                <span class="card-icon">👥</span>
                <h3>Students</h3>
                <p>Enrollment details</p>
                <a href="#students/students.php" class="btn">View Students</a>
            </div>
        </div>
    </main>

</body>
</html>