# Student Placement Management System

A compact PHP and MySQL placement-office portal. The administrator is the only user who can sign in. Use the portal to manage student records, companies, placement drives, applications, interview schedules, test questions and results, placements, and announcements.

## Setup

1. Copy this project folder into `C:\xampp\htdocs`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`, choose **Import**, select `database/database.sql`, and click **Go**.
4. Open `http://localhost/student-placement-management-system/`.
5. Sign in with the seeded demo account:

   - Email: `admin@college.edu`
   - Password: `Admin@123`

The SQL creates the `student_placement_system` database, its tables, and sample data. It drops and recreates those tables when imported; back up any data you need before importing it again. Change the demo password before using real data.

If the database already exists but the demo admin user is missing, import `database/demo_admin.sql` in phpMyAdmin to add or reset the demo account. The login page's **Use Demo Admin Login** button fills in these credentials; authentication still checks the database.

## Project layout

```text
admin/       Admin dashboard and placement-management pages
assets/      Shared stylesheet and small UI script
auth/        Admin sign-in handler
config/      Database connection, sessions, and shared helpers
database/    MySQL schema and sample data
includes/    Shared admin layout and navigation
uploads/     Student resume files
index.php    Redirects to sign-in or the admin dashboard
login.php    Admin sign-in page
logout.php   Ends the admin session
```

## Admin pages

- **Placement:** dashboard, students, companies, drives, applications/interviews, and placements.
- **Assessments:** aptitude and coding test management, questions, and results.
- **Updates:** announcements and student inquiries.

Students, companies, and recruiter details are placement records managed by the administrator; student and recruiter accounts cannot sign in.
