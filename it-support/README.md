# IT Support Management System

A complete PHP/MySQL IT Support CRUD application for hardware issue tracking.

## Features

- **Issue Tracking**: PC, UPS, CCTV, Printer, IP Phone, Hardware, Others
- **Role-Based Access**: 6 category roles + Admin
- **Dashboard**: Clickable Chart.js charts (category, status, monthly trend)
- **Reports**: Filter by category, date, name, branch code/name, branch type
- **Audit Logs**: Timeline view for all changes per issue
- **Dark/Light Theme**: Toggle with cookie persistence
- **Branch Management**: Branch, Sub-Branch, MBO, FT, Division
- **User Management**: Admin can create/modify users
- **Fully Offline**: Bootstrap, Chart.js, jQuery served locally
- **Small Monitor Optimized**: Compact, responsive design

## Requirements

- PHP 7.4+ with PDO extension
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx)

## Installation

### 1. Database Setup

```bash
mysql -u root -p < database.sql
```

Or import `database.sql` via phpMyAdmin.

### 2. Download Offline Vendor Assets

Run the following commands (or manually download and place in the respective folders):

```bash
# Bootstrap 5.3.2
curl -o assets/vendor/bootstrap/css/bootstrap.min.css \
  https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css
curl -o assets/vendor/bootstrap/js/bootstrap.bundle.min.js \
  https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js

# Bootstrap Icons 1.11.3
curl -o assets/vendor/bootstrap/css/bootstrap-icons.min.css \
  https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css
# Also download the fonts folder from bootstrap-icons into assets/vendor/bootstrap/fonts/

# Chart.js 4.4.1
curl -o assets/vendor/chartjs/chart.min.js \
  https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js

# jQuery 3.7.1
mkdir -p assets/vendor/jquery
curl -o assets/vendor/jquery/jquery.min.js \
  https://code.jquery.com/jquery-3.7.1.min.js
```

### 3. Configure Database

Edit `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'it_support');
define('BASE_URL', '/it-support/');
```

### 4. Place Files

Copy the `it-support/` folder to your web server's document root:
- Apache: `/var/www/html/it-support/`
- XAMPP: `C:/xampp/htdocs/it-support/`
- WAMP: `C:/wamp64/www/it-support/`

### 5. Access the Application

Navigate to: `http://localhost/it-support/`

**Default Login:**
- Username: `admin`
- Password: `admin123`

> ⚠️ Change the default password immediately after first login!

## User Roles

| Role     | Access                          |
|----------|---------------------------------|
| admin    | Full access to all issues       |
| pc       | PC category issues only         |
| ups      | UPS category issues only        |
| cctv     | CCTV category issues only       |
| printer  | Printer category issues only    |
| ipphone  | IP Phone category issues only   |
| hardware | Hardware category issues only   |

## Issue Fields

| Field               | Description                              |
|--------------------|------------------------------------------|
| IssueID            | Auto-generated unique ID                 |
| Category           | PC/UPS/CCTV/Printer/IP Phone/Hardware/Others |
| Issue Date         | Date problem occurred                    |
| Vendor Name        | Vendor involved                          |
| Branch-ID          | Branch identifier                        |
| Branch/Division    | Branch name and type                     |
| Branch Officer     | Contact person at branch                 |
| Contact/IPPHONE    | Phone number or IP Phone extension       |
| Problem Description| Detailed problem description             |
| Remarks            | Additional notes                         |
| Officer IT         | IT staff assigned                        |
| Status             | Open / In Progress / Resolved / Closed   |
| Last Update Officer| Who last updated                         |
| Last Update Date   | When last updated                        |
| Solved             | Yes/No                                   |
| Manager Name       | Responsible manager                      |

## Directory Structure

```
it-support/
├── config.php              # Database config & helpers
├── index.php               # Dashboard
├── login.php               # Login page
├── logout.php              # Logout
├── profile.php             # User profile
├── database.sql            # Database schema
├── problems/
│   ├── list.php            # Issue list with filters
│   ├── add.php             # Add new issue
│   ├── edit.php            # Edit issue
│   └── view.php            # View issue + audit timeline
├── reports/
│   └── index.php           # Report page with filters
├── admin/
│   ├── users.php           # User management
│   ├── user_add.php        # Add user
│   ├── user_edit.php       # Edit user
│   ├── branches.php        # Branch management
│   └── audit_logs.php      # Audit log viewer
├── ajax/
│   └── theme.php           # Theme toggle AJAX
├── includes/
│   ├── header.php          # HTML head
│   ├── footer.php          # Footer scripts
│   └── navbar.php          # Navigation bar
└── assets/
    ├── css/
    │   └── app.css         # Custom styles
    ├── js/
    │   └── app.js          # Custom scripts
    └── vendor/
        ├── bootstrap/      # Bootstrap CSS+JS (download separately)
        ├── chartjs/        # Chart.js (download separately)
        └── jquery/         # jQuery (download separately)
```
