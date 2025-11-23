CityCare - Smart Reporting Platform
ALL PASSWORDS: 12345678
| Role                  | Username        | Email                                                           |
| --------------------- | --------------- | --------------------------------------------------------------- |
| Admin                 | Admin           | [admin@admin.com](mailto:admin@admin.com)                       |
| Authority (Road)      | RoadKs          | [roadks@ks.com](mailto:roadks@ks.com)                           |
| Authority (Lighting)  | LightInsperctor | [lightInspector@gmail.com](mailto:lightInspector@gmail.com)     |
| Authority (Vandalism) | VandalismKs     | [vandalismks@gmail.com](mailto:vandalismks@gmail.com)           |
| Civilian              | user            | [user@gmail.com](mailto:user@gmail.com)                         |
| Municipality Head     | MuncipalityHead | [municipalityhead@gmail.com](mailto:municipalityhead@gmail.com) |


A complete, production-like city reporting platform built with PHP, MySQL, Bootstrap, and Leaflet maps. Includes AI-powered prioritization and cluster detection for efficient city management.

Features

Three User Roles: Civilian (report submission), Municipality Head (management), Admin (full access)

AI Integration: OpenRouter API for intelligent report prioritization and user assistance

Map Integration: Leaflet maps with OpenStreetMap for location selection and visualization

Image Storage: BLOB storage in MySQL database

Session-based Authentication: Secure login with password hashing

Responsive Design: Mobile-first Bootstrap 5 interface

Cluster Detection: Automatic detection of report clusters for recurring issues

Preloaded Dangerous Reports: Includes sample high-priority hazard reports (bridge collapse, floods, etc.)

Requirements

PHP 7.4+

MySQL 5.7+ / MariaDB 10.2+

phpMyAdmin (for database import)

Web server (Apache/Nginx) or PHP built-in server

OpenRouter API key (optional for AI features)

Installation
1. Database Setup

Open phpMyAdmin

Create a new database citycare

Import the SQL dump:

Go to the citycare database

Click Import

Choose file: migrations/db.sql

Click Go

⚠️ The SQL dump already includes all demo users, authorities, and sample reports, including dangerous reports for testing.

2. Configuration

Copy config.php.example to config.php:

cp config.php.example config.php


Update config.php with your database and OpenRouter API credentials:

define('DB_HOST', 'localhost');
define('DB_NAME', 'citycare');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password

// OpenRouter API for AI features
define('OPENROUTER_API_KEY', 'your-api-key-here'); // e.g. sk-or-XXXX
define('OPENROUTER_MODEL', 'kwaipilot/kat-coder-pro-v1:free');

define('APP_URL', 'http://localhost:8000');

3. Seed Demo Data (Optional)

Run the seed script to regenerate demo data:

php seed/seed.php


Or via browser: http://localhost:8000/seed/seed.php

This will create:

Demo user accounts

Sample authorities

Sample reports (including high-priority hazards)

AI priority logs (if OpenRouter key is configured)

4. Start Server

PHP Built-in Server:

cd public
php -S localhost:8000


Or configure Apache/Nginx to point to public/.

Access the application: http://localhost:8000

Demo Users Login

Admin: Admin / 12345678

Municipality Head: MuncipalityHead / 12345678

Civilian Users: user / 12345678

Authorities:

RoadKs / 12345678

LightInsperctor / 12345678

VandalismKs / 12345678

Project Structure
CityCare/
├── app/                    # Core application logic
│   ├── ai.php             # AI/OpenRouter integration
│   ├── auth.php           # Authentication functions
│   ├── db.php             # Database connection
│   ├── helpers.php        # Utility functions
│   ├── reports.php        # Report CRUD operations
│   └── authorities.php    # Authority management
├── assets/
│   ├── css/
│   │   └── style.css      # Custom styles
│   └── js/
│       └── map.js         # Leaflet map helpers
├── includes/
│   └── navbar.php        # Navigation component
├── migrations/
│   └── db.sql            # Database schema
├── public/               # Public-facing pages
│   ├── index.php        # Landing page
│   ├── login.php        # Login page
│   ├── register.php     # Registration page
│   ├── logout.php       # Logout handler
│   ├── user/            # Civilian pages
│   │   ├── dashboard.php
│   │   ├── submit_report.php
│   │   └── report_view.php
│   ├── municipality/    # Municipality pages
│   │   ├── dashboard.php
│   │   └── report_view.php
│   └── admin/           # Admin pages
│       └── panel.php
├── seed/
│   └── seed.php         # Demo data seeder
├── config.php.example   # Configuration template
└── README.md           # This file

AI Features (OpenRouter)

Priority Scoring: Automatically analyzes reports and assigns priority (1–5) based on urgency

User Assistant: Helps users create better reports by suggesting titles, categories, and summaries

AI features will gracefully degrade if the OpenRouter API key is not configured. Manual prioritization still works.

Using ngrok for External Access

Install ngrok: https://ngrok.com/

Start PHP server: php -S localhost:8000

Run: ngrok http 8000

Use the ngrok URL (e.g., https://abc123.ngrok.io) and update APP_URL in config.php

Security Features

CSRF protection

SQL injection prevention via PDO prepared statements

XSS protection via output escaping

Password hashing with password_hash()

Session security (session_regenerate_id() on login)

File upload validation (MIME type and size checks)

Troubleshooting

Database connection issues → check credentials in config.php and that MySQL is running

Image upload issues → check PHP upload_max_filesize and post_max_size

Map not loading → check Leaflet/OpenStreetMap and browser console

AI features → ensure valid OpenRouter API key with credits

License

This project is provided as-is for demonstration purposes.