# SIMPLE PLAN

## 1) DATABASE (MySQL)
1. Create database: `usjr`
2. Import SQL: `database/usjr-database.sql`
3. Set DB connection in: `database/db.php`

## 2) RUN THE SYSTEM
1. Open: `login.php`
2. Login (default):
   - admin / admin
   - staff / staff

## 3) SCREENS (CRUD)
- Home: `screens/homepage.php`
- Schools: list / create / update / delete
- Departments: list / create / update / delete (+ choose school)
- Programs: list / create / update / delete (+ filter)
- Students: list / create / update / delete
- Users: list / create / update / delete

## NOTE
- If you see DB error: import the SQL again and re-check `database/db.php`.

