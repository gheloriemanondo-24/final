<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department List - USJ-R School Management System</title>
    <!-- Linking to your existing CSS file -->
    <link rel="stylesheet" href="departments.css">
</head>
<body>

    <!-- TOPBAR -->
    <header class="topbar">
        <a class="topbar-brand" href="index.html">USJ-R School Management System v1.01</a>
        <div class="topbar-right">
            <span class="user-info">You are logged in as: <strong>AdminUser</strong> | 👤</span>
            <form action="index.html" method="GET" style="margin:0; display: inline;">
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </header>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="schoolList.html">Schools</a></li>
                <!-- We set Departments to 'active' since we are on the Department List page -->
                <li><a href="departmentList.html" class="active">Departments</a></li>
                <li><a href="programList.html">Programs</a></li>
                <li><a href="studentList.html">Students</a></li>
                <li><a href="userList.html">Users</a></li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <div class="section-header">
            <h2>Department List - College of Information, Computer and Communications Technology</h2>
        </div>

        <!-- Alert Example -->
        <div class="alert alert-success">
            ✅ Department list loaded successfully.
        </div>

        <!-- Action Buttons -->
        <div style="margin-bottom:14px; display:flex; gap:10px;">
            <a href="departmentCreate.html" class="btn btn-green">➕ Create Department Entry</a>
            <a href="chooseSchool.html" class="btn btn-red">⬅ Back to Schools</a>
        </div>

        <!-- Table Section -->
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
                    <tr>
                        <td>101</td>
                        <td>Department of Computer Studies</td>
                        <td>DCS</td>
                        <td>
                            <a href="departmentUpdate.html" class="btn btn-green btn-sm">✏️ Update</a>
                            <button onclick="confirmDelete()" class="btn btn-red btn-sm">🗑️ Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>102</td>
                        <td>Department of Information Technology</td>
                        <td>DIT</td>
                        <td>
                            <a href="departmentUpdate.html" class="btn btn-green btn-sm">✏️ Update</a>
                            <button onclick="confirmDelete()" class="btn btn-red btn-sm">🗑️ Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>103</td>
                        <td>Department of Library and Information Science</td>
                        <td>DLIS</td>
                        <td>
                            <a href="departmentUpdate.html" class="btn btn-green btn-sm">✏️ Update</a>
                            <button onclick="confirmDelete()" class="btn btn-red btn-sm">🗑️ Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="total-row">Total of: 3 departments in the database</p>

    </main>

    <!-- Simple Script for Delete Confirmation -->
    <script>
        function confirmDelete() {
            if (confirm("Are you sure you want to delete this department?")) {
                alert("This is a static HTML demo. In a live system, this record would now be deleted from the database.");
            }
        }
    </script>

</body>
</html>