# USJ-R School Management System (PHP) — Study Documentation
Last updated: 2026-05-20

This document explains the **purpose**, **logic**, and **flow** of the PHP code in `Final/` so you can study how the system works.

---

## 1) Project structure (Final)

```
Final/
  database/
    db.php
    Service.php
    usjr-database.sql
  screens/
    homepage.php
    schools/      (list/create/update/delete)
    departments/  (list/create/update/delete)
    programs/     (list/create/update/delete + filter)
    students/     (list/create/update/delete)
    users/        (dashboard + manage + import + settings + delete)
  assets/
    website.css   (shared theme)
    ui.js         (shared UI effects)
  login.php
```

### How pages work (pattern used everywhere)
Every page follows this high-level pattern:
1. **Include the service bootstrap** (`database/Service.php`) to get:
   - `$pdo` (database connection)
   - session + login helper functions
   - shared helper `h()` for HTML escaping
2. **Require login** using `requireLogin(...)` so only authenticated users can access.
3. **Read input** from `$_GET` or `$_POST`.
4. **Validate input** (required fields, ID formats, relationships).
5. **Run SQL** with PDO prepared statements (safe and prevents SQL injection).
6. **Redirect after success** using `header('Location: ...?msg=...')` (prevents form re-submit on refresh).
7. **Render HTML**: topbar + sidebar + main content.

---

## 2) Database schema (MySQL)

The database is defined in `database/usjr-database.sql`.

### Main tables
- `colleges` (Schools)
  - `collid` (INT, PK)
  - `collfullname` (VARCHAR)
  - `collshortname` (VARCHAR)
- `departments`
  - `deptid` (INT, PK)
  - `deptfullname` (VARCHAR)
  - `deptshortname` (VARCHAR)
  - `deptcollid` (INT, FK → colleges.collid)
- `programs`
  - `progid` (INT, PK)
  - `progfullname` (VARCHAR)
  - `progshortname` (VARCHAR)
  - `progcollid` (INT, FK → colleges.collid)
  - `progcolldeptid` (INT, FK → departments.deptid)
- `students`
  - `studid` (INT, PK)
  - `studfirstname`, `studmidname`, `studlastname`
  - `studcollid` (FK → colleges)
  - `studcolldeptid` (FK → departments)
  - `studprogid` (FK → programs)
  - `studyear` (INT)
- `users`
  - `username` (PK)
  - `password`
  - `usertype` (e.g., Administrator / Staff)
  - `userrole` (e.g., Administrator / Staff)
  - `status` (1 active, 0 inactive)

### Relationships (why delete restrictions matter)
Because of foreign keys:
- A department is a “parent” of programs (`programs.progcolldeptid`).
- A program is a “parent” of students (`students.studprogid`).

So the UI adds checks like:
> “Cannot delete department record because it is associated with existing programs.”

---

## 3) Database connection and shared helpers

### `database/db.php`
**Purpose:** create the PDO connection (`$pdo`).

**Logic:**
- Defines DB connection parameters
- Creates PDO instance and enables exceptions

This lets every page do database queries through `$pdo`.

### `database/Service.php`
This is the most important shared file.

**Purpose:**
- Start session (`session_start()`)
- Load `$pdo` (`require db.php`)
- Provide shared helper functions:
  - `h()` (escape output)
  - `tableHasColumn()` (schema-compatibility helper)
  - `currentUser()`, `requireLogin()`
  - `loginUser()`, `logoutUser()`
- Seed a default admin account (so the system works after SQL import)
- Remove legacy “Others” placeholder rows (and legacy users with role “others”)

### `h($value)`
**Purpose:** prevent XSS (cross-site scripting).

**Logic:** converts `<`, `>`, `"`, `'` into safe HTML entities.  
Example:
```php
<td><?= h($row['collfullname']) ?></td>
```

### `requireLogin($loginPath)`
**Purpose:** protect pages from unauthenticated access.

**Logic:**
- If `$_SESSION['user']` is missing → redirect to login.

### `loginUser($username, $password)`
**Purpose:** authenticate the user.

**Logic:**
1. Look up user in DB by username
2. Verify user is active (`status == 1`)
3. Verify password:
   - If the stored password looks like a hash (bcrypt/argon2) → `password_verify`
   - Else → plain comparison (legacy)
4. Store session info in `$_SESSION['user']`

---

## 4) Global UI rules implemented in screens

### A) 5-per-page lists + Prev/Next
List pages use:
- `$page = max(1, (int)($_GET['page'] ?? 1));`
- `$perPage = 5;`
- `$offset = ($page - 1) * $perPage;`
- `LIMIT :limit OFFSET :offset`

Then the page renders Prev/Next links by decreasing/increasing `page` in the URL.

### B) Removing “Others”
Your requirement: **no “Others” must appear**.

So the code:
- Filters out ID `0` rows in list queries, e.g. `WHERE collid <> 0`
- Deletes legacy placeholder rows in `Service.php`
- Removes “Others” option from user role dropdowns and hides/normalizes legacy “others” values

---

## 5) School module (colleges)
Folder: `screens/schools/`

### `schools.php` (School List)
**Purpose:** show schools with actions.

**Logic:**
- Count total rows (excluding ID 0)
- Fetch only 5 per page
- Render table with:
  - School ID
  - School Full Name
  - School Short Name
  - Actions (Update/Delete)

### `schoolCreate.php` (School Create)
**Required fields:**
- School ID (numbers only; example: `11`)
- School Full Name
- School Short Name

**Logic:**
- Validate School ID is **digits only** (server-side via `isDigitsOnly(...)`)
- Insert into `colleges`
- Redirect to list with `?msg=created`

**Buttons:**
- Save New School Entry
- Reset Form
- Exit

### `schoolUpdate.php` (School Update)
**Only fields allowed:**
- School ID (readonly)
- School Full Name
- School Short Name

**Buttons:**
- Update School Entry
- Reset Form
- Exit

### `schoolDelete.php` (School Delete)
**Purpose:** confirmation + delete.

**Logic:**
- Load school by `collid`
- Show a confirmation table
- On POST: attempt delete
- If DB blocks it because of relationships, show the error message

---

## 6) Department module
Folder: `screens/departments/`

### `departments.php` (Department List)
**Purpose:** list departments (5 per page) with school context.

**Logic:**
- Joins `departments` with `colleges` to show which school a department belongs to
- Supports filtering by school (depending on UI)

### `departmentCreate.php`
**Fields:**
- Department ID (numbers only; example: `11001`)
- Department Full Name
- Department Short Name
- Select School

**Logic:**
- Validate required fields + Department ID is **digits only** (server-side via `isDigitsOnly(...)`)
- Insert into `departments`

### `departmentUpdate.php`
**Purpose:** edit department details.

**Buttons required:**
- Update Department Entry
- Reset Form
- Exit

### `departmentDelete.php`
This page matches your required delete layout:
- “You are about to delete the following department…”
- Table of Department ID / Full Name / Short Name
- Warning text about relationships
- Buttons: `[Yes, Delete Entry] [No, Cancel]`

**Important logic: “cannot delete if programs exist”**
It counts programs first:
```sql
SELECT COUNT(*) FROM programs
WHERE progcolldeptid = ? AND progid <> 0
```
If count > 0 → show:
> Cannot delete department record because it is associated with existing programs.

---

## 7) Program module
Folder: `screens/programs/`

### `programs.php` (Program List + required filter at top)
Your requirement: the page should start with:
- Select School (dropdown)
- Select Department (dropdown)

**Dependency logic (JavaScript):**
- If a school is selected → hide departments not under that school
- If a department is chosen first (and school not chosen) → auto-select the school of that department
- Department dropdown is enabled immediately after selecting a school

**Server-side filtering logic:**
- If `collid` is selected → filter by `p.progcollid = ?`
- If `deptid` is selected → filter by `p.progcolldeptid = ?`

### `programCreate.php`
**Fields:**
- Select School
- Select Department (filtered by selected school)
- Program ID
- Program Full Name
- Program Short Name

**Program ID rule implemented:**
- Numbers only
- Exactly **10 digits**
- Example: `2111001001`
> Note: the DB column is `INT`, so values must still fit `<= 2147483647` to insert successfully.

**Buttons (standard create):**
- Save New Program Entry
- Back
- Reset Form
- Exit

### `programUpdate.php`
**Buttons (standard update):**
- Update Program Entry
- Reset Form
- Exit

### `programDelete.php`
Shows confirmation and attempts delete; if students exist it may fail and show:
> Cannot delete this program (it may have linked students).

---

## 8) Student module
Folder: `screens/students/`

### `students.php` (Student List + required 3 dropdowns at top)
Your requirement: start with:
- Select School
- Select Department
- Select Program
Then show the student list for that selection.

**Dependency logic:**
- Department options depend on School (but if school not chosen, user can choose any department)
- Program options depend on School + Department
- If a program is selected, the page auto-syncs school/department

### `studentCreate.php`
**All fields required:**
- Student ID (numbers only; exactly 10 digits)
- Student First Name
- Student Middle Name
- Student Last Name
- Student Year
- Select School / Department / Program

**Student ID rule implemented:**
- Must be **exactly 10 digits** (numbers only)
- Example: `2111001001`
> Note: the DB column is `INT`, so Student ID must be `<= 2147483647` to insert successfully.

The page also shows your example line:
> `21: SOFA | 21001: DOFA | 2121001001`

### `studentDelete.php`
Matches your required delete flow:
- Table with ID / First / Middle / Last
- Buttons:
  - Cancel Operation
  - Proceed

---

## 9) Shared UI effects (theme enhancements)

These are **effects only** (no color changes) and apply system-wide:
- Page enter animation (subtle fade + slide)
- Breadcrumbs under the page title
- Sticky table headers + shadow on scroll (inside `.table-wrap`)
- Click-to-select table rows (highlight selected row)
- Form effects:
  - invalid field highlight + small shake
  - submit button loading spinner / disabled state
- “Format:” helper text upgraded to a tooltip (`?` icon)

**Files:**
- `assets/website.css`: shared theme + effects
- `assets/ui.js`: shared behavior (included via `<script ... defer>`)

---

## 10) User module (Dashboard + Manage + Import + Settings)
Folder: `screens/users/`

### `users.php` (User Dashboard)
Your requirement:
> Ask if you want to manage users or add users (2 buttons).

So this page shows:
- Manage Users
- Add Users

### `manageUsers.php` (Manage Users list)
**Rules implemented:**
- Shows 5 users per page + Prev/Next
- The action button is **Settings** (not Update), plus Delete

### `userUpdate.php` (User Settings page)
Matches your layout:
- User Account Details table:
  - User Name
  - User Type (dropdown)
  - User Role (dropdown)
- Password Settings table:
  - User Password
  - User Confirm Password
- Buttons:
  - Update User Settings
  - Reset Form
  - Exit
- Includes a **Reset Password** button (clears the password inputs so you can retype)

**Password logic:**
- If password is empty → keep existing password
- If password is provided → must match confirm password → stored as `password_hash(...)`

### `userImport.php` (Add Users From File)
Matches your required UI:
- Choose File (CSV)
- Upload
- Exit

**CSV logic (purpose):**
- Reads CSV file line-by-line
- For each username:
  - If username already exists → skip insertion and show message:
    - `User already exists: jcg. Skipping insertion.`

**Error summary required:**
If any row is skipped/failed, the page prints:
> File processed with some errors. Please review the error messages.

**CSV supported formats:**
- With header row: `username,password,usertype,userrole`
- Or without header (treated as the same order)

#### CSV format details (recommended)

**File type:** `.csv` (comma-separated)  
**Text encoding:** UTF-8 (recommended)

##### Option A — CSV WITH header row (recommended)
First line must be exactly:
```csv
username,password,usertype,userrole
```

Then each next line is one user:
```csv
jcg,12345,Staff,Staff
cmt,12345,Staff,Staff
admin2,12345,Administrator,Administrator
```

##### Option B — CSV WITHOUT header row
Same order as the header, but no header line:
```csv
jcg,12345,Staff,Staff
cmt,12345,Staff,Staff
admin2,12345,Administrator,Administrator
```

##### Column meaning (what each column is for)
| Column | Required | Meaning | Example |
|---|---:|---|---|
| `username` | Yes | Login username (must be unique) | `jcg` |
| `password` | Yes | Plain password from CSV (system will hash it) | `12345` |
| `usertype` | Depends on DB schema | User Type dropdown value | `Staff` / `Administrator` |
| `userrole` | Depends on DB schema | User Role dropdown value | `Staff` / `Administrator` |

##### Important rules enforced by the importer
- If `username` already exists → it will **skip** and show: `User already exists: <name>. Skipping insertion.`
- If `password` is missing → it will **skip** and show a missing password message.
- If `usertype/userrole` is `Others` → it will be converted to `Staff` (so **Others will not appear**).

> Note: “Others” role values are automatically normalized to “Staff” so “Others” never appears.

---

## 11) Study tips (how to learn from this code)

If you want to study effectively, follow this order:
1. `database/usjr-database.sql` → understand tables + foreign keys
2. `database/Service.php` → understand authentication + helpers + shared logic
3. Open one module at a time:
   - list page (pagination + filters)
   - create page (POST + validate + INSERT + redirect)
   - update page (load row + POST + validate + UPDATE + redirect)
   - delete page (confirmation + relationship checks + DELETE)

---

## 12) File-by-file index (quick reference)

- Auth
  - `login.php`: login form + calls `loginUser()`
- Shared
  - `database/db.php`: PDO connection
  - `database/Service.php`: helpers + auth + cleanup
- Schools
  - `screens/schools/schools.php`: list (5 per page)
  - `screens/schools/schoolCreate.php`
  - `screens/schools/schoolUpdate.php`
  - `screens/schools/schoolDelete.php`
- Departments
  - `screens/departments/departments.php`
  - `screens/departments/departmentCreate.php`
  - `screens/departments/departmentUpdate.php`
  - `screens/departments/departmentDelete.php`
- Programs
  - `screens/programs/programs.php` (filter at top + list)
  - `screens/programs/programCreate.php`
  - `screens/programs/programUpdate.php`
  - `screens/programs/programDelete.php`
- Students
  - `screens/students/students.php` (3 dropdowns + list)
  - `screens/students/studentCreate.php`
  - `screens/students/studentUpdate.php`
  - `screens/students/studentDelete.php`
- Users
  - `screens/users/users.php` (dashboard)
  - `screens/users/manageUsers.php` (list)
  - `screens/users/userUpdate.php` (settings)
  - `screens/users/userDelete.php`
  - `screens/users/userImport.php` (CSV import)
