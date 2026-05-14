<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Department - USJ-R SMS</title>
    <!-- Linking to your existing stylesheet -->
    <link rel="stylesheet" href="departments.css">
</head>
<body>

    <!-- TOPBAR -->
    <header class="topbar">
        <a class="topbar-brand" href="index.html">USJ-R School Management System v1.01</a>
        <div class="topbar-right">
            <span class="user-info">You are logged in as: <strong>AdminUser</strong> | 👤</span>
            <button class="btn-logout">Logout</button>
        </div>
    </header>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="schoolList.html">Schools</a></li>
                <li><a href="departmentList.html" class="active">Departments</a></li>
                <li><a href="programList.html">Programs</a></li>
                <li><a href="studentList.html">Students</a></li>
                <li><a href="userList.html">Users</a></li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div style="padding: 8px 0; max-width: 860px;">
            
            <div class="section-header">
                <h2>Department Create - CAS: College of Arts and Sciences</h2>
            </div>

            <!-- This would show after a successful PHP save, kept for layout demonstration -->
            <div class="alert alert-success" id="successMessage" style="display:none;">
                ✅ Department entry created successfully
            </div>

            <form id="deptCreateForm">
                <!-- Department ID -->
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Department ID:</label>
                    <input type="number" id="deptid" placeholder="e.g. 10101">
                    <span class="error-msg" id="err-id"></span>
                </div>

                <!-- Department Full Name -->
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Department Full Name:</label>
                    <input type="text" id="deptfullname" placeholder="e.g. Department of Computer Studies">
                    <span class="error-msg" id="err-name"></span>
                </div>

                <!-- Department Short Name -->
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>Department Short Name:</label>
                    <input type="text" id="deptshortname" placeholder="e.g. DCS">
                    <span class="error-msg"></span>
                </div>

                <!-- School Selection (Static version of the PHP loop) -->
                <div class="form-row" style="display:grid; grid-template-columns: 180px 360px 1fr; align-items:center; gap:10px; margin-bottom:12px;">
                    <label>School:</label>
                    <select id="deptcollid">
                        <option value="1">College of Arts and Sciences</option>
                        <option value="2">College of Engineering</option>
                        <option value="3">College of Commerce</option>
                    </select>
                    <span class="error-msg"></span>
                </div>

                <!-- Form Actions -->
                <div class="form-actions" style="display:flex; gap:8px; margin-top:16px;">
                    <button type="button" class="btn btn-gray" onclick="handleSave()">Save New Department Entry</button>
                    <button type="button" class="btn btn-gray" onclick="resetForm()">Reset Form</button>
                    <a href="departmentList.html" class="btn btn-red">Exit</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        function handleSave() {
            // Static demonstration of validation
            const deptId = document.getElementById('deptid').value;
            const deptName = document.getElementById('deptfullname').value;
            
            if (!deptId || !deptName) {
                document.getElementById('err-id').textContent = !deptId ? 'ID is required' : '';
                document.getElementById('err-name').textContent = !deptName ? 'Name is required' : '';
            } else {
                document.getElementById('successMessage').style.display = 'block';
                // In a real app, this is where the AJAX or PHP POST would happen
            }
        }

        function resetForm() {
            document.getElementById('deptid').value = '';
            document.getElementById('deptfullname').value = '';
            document.getElementById('deptshortname').value = '';
            document.getElementById('successMessage').style.display = 'none';
            document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');
        }
    </script>

</body>
</html>