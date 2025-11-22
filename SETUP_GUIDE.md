# CityCare Setup Guide for Windows/XAMPP

## Issue 1: PHP Not in PATH

**Problem:** PowerShell doesn't recognize `php` command because PHP isn't in your system PATH.

**Solution:** Use the full path to XAMPP's PHP: `C:\xampp\php\php.exe`

## Issue 2: PowerShell Syntax

**Problem:** PowerShell doesn't support `&&` operator (that's for bash/cmd).

**Solution:** Use `;` or run commands separately in PowerShell.

---

## Step-by-Step Setup

### Step 1: Create Database in phpMyAdmin

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "New" in the left sidebar
3. Database name: `citycare`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

### Step 2: Import Database Schema

1. In phpMyAdmin, select the `citycare` database
2. Click "Import" tab
3. Click "Choose File" and select: `migrations/db.sql`
4. Click "Go" at the bottom
5. Wait for "Import has been successfully finished" message

### Step 3: Run Seed Script

**Option A: Using PowerShell (Command Line)**

```powershell
# Run seed script
C:\xampp\php\php.exe seed/seed.php
```

**Option B: Using Web Browser (Easier!)**

1. Start your web server first (see Step 4)
2. Open browser: http://localhost:8000/seed/seed.php
3. The seed script will run and show output in the browser

### Step 4: Start Web Server

**PowerShell Commands (use semicolon, not &&):**

```powershell
# Navigate to public folder
cd public

# Start PHP server
C:\xampp\php\php.exe -S localhost:8000
```

**Or as separate commands:**
```powershell
cd public
C:\xampp\php\php.exe -S localhost:8000
```

**Alternative: Use XAMPP Apache**

If you prefer using XAMPP's Apache:
1. Start Apache from XAMPP Control Panel
2. Access via: http://localhost/ChampionTrials2/public/

### Step 5: Access the Website

Open your browser: http://localhost:8000

---

## Quick Reference: PowerShell Commands

```powershell
# Run seed script
C:\xampp\php\php.exe seed/seed.php

# Start web server (from project root)
cd public; C:\xampp\php\php.exe -S localhost:8000

# Or separately:
cd public
C:\xampp\php\php.exe -S localhost:8000
```

## Troubleshooting

### Database Connection Error
- Make sure MySQL is running in XAMPP Control Panel
- Verify database name in `config.php` matches what you created
- Check username/password in `config.php` (default: root with no password)

### PHP Not Found
- Always use full path: `C:\xampp\php\php.exe`
- Or add `C:\xampp\php` to your system PATH (optional)

### Port Already in Use
- Change port: `C:\xampp\php\php.exe -S localhost:8001`
- Or stop other services using port 8000

