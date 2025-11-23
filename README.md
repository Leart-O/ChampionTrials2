# CityCare - Smart Reporting Platform

A complete, production-like city reporting platform built with pure PHP, MySQL, Bootstrap, and Leaflet maps. Features AI-powered prioritization and cluster detection for efficient city management.
ALL PASSWORDS: 12345678

| Role                  | Username        | Email                                                           |
| --------------------- | --------------- | --------------------------------------------------------------- |
| Admin                 | Admin           | [admin@admin.com](mailto:admin@admin.com)                       |
| Authority (Road)      | RoadKs          | [roadks@ks.com](mailto:roadks@ks.com)                           |
| Authority (Lighting)  | LightInsperctor | [lightInspector@gmail.com](mailto:lightInspector@gmail.com)     |
| Authority (Vandalism) | VandalismKs     | [vandalismks@gmail.com](mailto:vandalismks@gmail.com)           |
| Civilian              | user            | [user@gmail.com](mailto:user@gmail.com)                         |
| Municipality Head     | MuncipalityHead | [municipalityhead@gmail.com](mailto:municipalityhead@gmail.com) |


## Features

- **Three User Roles**: Civilian (report submission), Municipality Head (management), Admin (full access)
 - **AI Integration**: OpenRouter API for intelligent report prioritization and user assistance
- **Map Integration**: Leaflet maps with OpenStreetMap for location selection and visualization
- **Image Storage**: BLOB storage in MySQL database
- **Session-based Authentication**: Secure login with password hashing
- **Responsive Design**: Mobile-first Bootstrap 5 interface
- **Cluster Detection**: Automatic detection of report clusters for recurring issues

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- phpMyAdmin (for database import)
- Web server (Apache/Nginx) or PHP built-in server
 - openRouter API key (optional, for AI features)

## Installation

### 1. Database Setup

1. Open phpMyAdmin
2. Create a new database named `citycare` (or your preferred name)
3. Import the schema file:
   - Go to the `citycare` database
   - Click "Import" tab
   - Choose file: `migrations/db.sql`
   - Click "Go"

### 2. Configuration

1. Copy the example configuration file:
   ```bash
   cp config.php.example config.php
   ```

2. Edit `config.php` and update the following:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'citycare');
   define('DB_USER', 'root');        // Your MySQL username
   define('DB_PASS', '');            // Your MySQL password
   
   // openRouter API (get key from https://openRouter.com/)
   define('openRouter_API_KEY', 'your-api-key-here');
   define('OPENROUTER_MODEL', 'kwaipilot/kat-coder-pro-v1:free');
   
   define('APP_URL', 'http://localhost:8000');
   ```

### 3. Seed Demo Data

Run the seed script to populate the database with demo users and reports:

**From command line:**
```bash
php seed/seed.php
```

**Or via web browser:**
Visit: `http://localhost:8000/seed/seed.php`

The seed script will:
- Create demo user accounts (admin, municipality, civilians)
- Create a demo authority
- Insert sample reports with images
- Run AI priority analysis (if API key is configured)

**Note:** The seed script looks for an image at `/mnt/data/B208173A-EE2F-45DB-95D7-E60B8352067A.jpeg`. If the file is not found, a placeholder will be created.

### 4. Start the Server

**Using PHP built-in server:**
```bash
cd public
php -S localhost:8000
```

**Or configure your web server:**
- Point document root to the `public` directory
- Ensure mod_rewrite is enabled (if using Apache)

### 5. Access the Application

Open your browser and navigate to:
- **Local:** http://localhost:8000
- **Network:** http://your-ip:8000

## Demo Credentials

After running the seed script, you can login with:

### Admin
- **Username:** `admin_demo`
- **Password:** `DemoPass123!`

### Municipality Head
- **Username:** `muni_demo`
- **Password:** `DemoPass123!`

### Civilian Users
- **Username:** `user1` / **Password:** `DemoPass123!`
- **Username:** `user2` / **Password:** `DemoPass123!`

## Project Structure

```
CityCare/
├── app/                    # Core application logic
│   ├── ai.php             # AI/openRouter integration
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
```

### AI Features

The platform uses openRouter API for AI-powered features:

1. **Priority Scoring**: Automatically analyzes reports and assigns priority (1-5) based on urgency
2. **User Assistant**: Helps users create better reports by suggesting titles, categories, and summaries

### Setting Up openRouter

1. Sign up at https://openRouterRouter.com/
2. Get your API key from the dashboard
3. Add the key to `config.php`:
   ```php
   define('openRouter_API_KEY', 'gsk_...');
   ```
4. Choose a model (default: `openRouter-alpha`)

**Note:** AI features will gracefully degrade if the API key is not configured. The platform will work without AI, but prioritization will be manual.

## Using ngrok for External Access

To share your local development server:

1. Install ngrok: https://ngrok.com/
2. Start your PHP server: `php -S localhost:8000`
3. In another terminal: `ngrok http 8000`
4. Use the ngrok URL (e.g., `https://abc123.ngrok.io`) to access from anywhere

**Important:** Update `APP_URL` in `config.php` to match your ngrok URL for proper CSRF token validation.

## Security Features

- **CSRF Protection**: All forms include CSRF tokens
- **SQL Injection Prevention**: PDO prepared statements throughout
- **XSS Protection**: Output escaping with `h()` helper function
- **Password Hashing**: Uses PHP `password_hash()` with secure defaults
- **Session Security**: `session_regenerate_id()` on login
- **File Upload Validation**: MIME type and size checks

## Troubleshooting

### Database Connection Errors
- Verify MySQL is running
- Check credentials in `config.php`
- Ensure database `citycare` exists

### Image Upload Issues
- Check PHP `upload_max_filesize` and `post_max_size` settings
- Verify `MAX_UPLOAD_SIZE` in `config.php` (default: 5MB)

- Verify openRouter API key is set correctly
- Check API key has sufficient credits
- Review error logs in PHP error log

### Map Not Loading
- Ensure internet connection (Leaflet loads tiles from OpenStreetMap)
- Check browser console for JavaScript errors

## Development Notes

- **Error Display**: Set `display_errors = 1` in `config.php` for development
- **Production**: Set `display_errors = 0` and use proper error logging
- **Database**: All queries use PDO prepared statements
- **Images**: Stored as LONGBLOB in MySQL (consider file system storage for production)

## License

This project is provided as-is for demonstration purposes.

## Support

For issues or questions, please refer to the code comments or check the demo script (`demo-script.txt`) for a walkthrough of features.

.