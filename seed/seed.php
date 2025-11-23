<?php
/**
 * CityCare Seed Script
 * Inserts demo data including users, authorities, and reports with images
 * 
 * Usage: php seed/seed.php
 * Or visit: http://localhost:8000/seed/seed.php
 */

// Check if running from CLI or web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><title>CityCare Seed</title>";
    echo "<style>body{font-family:monospace;padding:20px;}</style></head><body>";
    echo "<h1>CityCare Database Seeding</h1><pre>";
}

require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';

$pdo = getDB();

echo "Starting database seeding...\n\n";

try {
    // Check if users already exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    if ($userCount > 0) {
        echo "Warning: Database already contains users. Skipping seed data.\n";
        echo "To re-seed, please clear the database first.\n";
        if (!$isCLI) {
            echo "</pre></body></html>";
        }
        exit;
    }
    
    // Create demo users
    echo "Creating demo users...\n";
    
    // Admin user
    $adminPassword = password_hash('DemoPass123!', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin_demo', 'admin@citycare.local', $adminPassword, 3]); // Admin role
    $adminId = $pdo->lastInsertId();
    echo "  ✓ Created admin_demo (ID: $adminId)\n";
    
    // Municipality Head
    $muniPassword = password_hash('DemoPass123!', PASSWORD_DEFAULT);
    $stmt->execute(['muni_demo', 'muni@citycare.local', $muniPassword, 2]); // Municipality Head role
    $muniId = $pdo->lastInsertId();
    echo "  ✓ Created muni_demo (ID: $muniId)\n";
    
    // Civilian users
    $user1Password = password_hash('DemoPass123!', PASSWORD_DEFAULT);
    $stmt->execute(['user1', 'user1@citycare.local', $user1Password, 1]); // Civilian role
    $user1Id = $pdo->lastInsertId();
    echo "  ✓ Created user1 (ID: $user1Id)\n";
    
    $user2Password = password_hash('DemoPass123!', PASSWORD_DEFAULT);
    $stmt->execute(['user2', 'user2@citycare.local', $user2Password, 1]); // Civilian role
    $user2Id = $pdo->lastInsertId();
    echo "  ✓ Created user2 (ID: $user2Id)\n";
    
    // Create demo authority
    echo "\nCreating demo authority...\n";
    $stmt = $pdo->prepare("INSERT INTO authorities (name, type, contact_email, notes) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Public Works Department', 'Department', 'publicworks@example.local', 'Handles infrastructure repairs']);
    $authorityId = $pdo->lastInsertId();
    echo "  ✓ Created authority: Public Works Department (ID: $authorityId)\n";
    
    // Load and insert demo image
    echo "\nLoading demo image...\n";
    $imagePath = '/mnt/data/B208173A-EE2F-45DB-95D7-E60B8352067A.jpeg';
    
    // Try multiple paths in case the file is in different locations
    $possiblePaths = [
        $imagePath,
        __DIR__ . '/../' . $imagePath,
        __DIR__ . '/../public/' . basename($imagePath),
        __DIR__ . '/' . basename($imagePath)
    ];
    
    $imageData = null;
    $imageFound = false;
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $imageData = file_get_contents($path);
            if ($imageData !== false) {
                $imageFound = true;
                echo "  ✓ Loaded image from: $path (" . number_format(strlen($imageData)) . " bytes)\n";
                break;
            }
        }
    }
    
    if (!$imageFound) {
        // Create a placeholder image if the file doesn't exist
        echo "  ⚠ Image file not found. Creating placeholder...\n";
        // Create a simple 1x1 pixel JPEG
        $imageData = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/8A');
        echo "  ✓ Created placeholder image\n";
    }
    
    // Create demo reports
    echo "\nCreating demo reports...\n";
    
    // Report 1: Pothole (with image)
    $stmt = $pdo->prepare("
        INSERT INTO reports (user_id, title, description, image, latitude, longitude, category, status_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bindParam(1, $user1Id, PDO::PARAM_INT);
    $stmt->bindParam(2, $title1, PDO::PARAM_STR);
    $stmt->bindParam(3, $desc1, PDO::PARAM_STR);
    $stmt->bindParam(4, $imageData, PDO::PARAM_LOB);
    $stmt->bindParam(5, $lat1, PDO::PARAM_STR);
    $stmt->bindParam(6, $lng1, PDO::PARAM_STR);
    $stmt->bindParam(7, $cat1, PDO::PARAM_STR);
    $stmt->bindParam(8, $status1, PDO::PARAM_INT);
    
    $title1 = 'Large Pothole on Main Street';
    $desc1 = 'There is a large pothole on Main Street near the intersection with Oak Avenue. It is approximately 2 feet wide and 6 inches deep. This is dangerous for vehicles and could cause damage. Please repair as soon as possible.';
    $lat1 = '40.7128';
    $lng1 = '-74.0060';
    $cat1 = 'pothole';
    $status1 = 1; // Pending
    
    $stmt->execute();
    $report1Id = $pdo->lastInsertId();
    echo "  ✓ Created report: $title1 (ID: $report1Id)\n";
    
    // Report 2: Lighting issue (without image)
    $title2 = 'Broken Street Light on Elm Street';
    $desc2 = 'The street light at the corner of Elm Street and Maple Drive has been out for over a week. This area is very dark at night and poses a safety risk for pedestrians.';
    $lat2 = '40.7580';
    $lng2 = '-73.9855';
    $cat2 = 'lighting';
    $status2 = 1; // Pending
    
    $stmt = $pdo->prepare("
        INSERT INTO reports (user_id, title, description, latitude, longitude, category, status_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user2Id, $title2, $desc2, $lat2, $lng2, $cat2, $status2]);
    $report2Id = $pdo->lastInsertId();
    echo "  ✓ Created report: $title2 (ID: $report2Id)\n";
    
    // Report 3: Water leak (with same image)
    $title3 = 'Water Leak on Park Avenue';
    $desc3 = 'There is a significant water leak coming from a broken pipe near the intersection of Park Avenue and 5th Street. Water is pooling on the sidewalk and flowing into the street. This needs immediate attention.';
    $lat3 = '40.7505';
    $lng3 = '-73.9934';
    $cat3 = 'water-leak';
    $status3 = 2; // In-Progress
    
    $stmt = $pdo->prepare("
        INSERT INTO reports (user_id, title, description, image, latitude, longitude, category, status_id, assigned_to)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bindParam(1, $user1Id, PDO::PARAM_INT);
    $stmt->bindParam(2, $title3, PDO::PARAM_STR);
    $stmt->bindParam(3, $desc3, PDO::PARAM_STR);
    $stmt->bindParam(4, $imageData, PDO::PARAM_LOB);
    $stmt->bindParam(5, $lat3, PDO::PARAM_STR);
    $stmt->bindParam(6, $lng3, PDO::PARAM_STR);
    $stmt->bindParam(7, $cat3, PDO::PARAM_STR);
    $stmt->bindParam(8, $status3, PDO::PARAM_INT);
    $stmt->bindParam(9, $authorityId, PDO::PARAM_INT);
    $stmt->execute();
    $report3Id = $pdo->lastInsertId();
    echo "  ✓ Created report: $title3 (ID: $report3Id)\n";
    
    // Run AI priority scoring for demo reports
    echo "\nRunning AI priority analysis...\n";
    require_once __DIR__ . '/../app/ai.php';
    
    // Check if OpenRouter AI API key is configured
    $openrouterConfigured = defined('OPENROUTER_API_KEY') && OPENROUTER_API_KEY !== '';

    if ($openrouterConfigured) {
        echo "  Running AI analysis for reports...\n";
        
        $reports = [
            ['id' => $report1Id, 'title' => $title1, 'desc' => $desc1, 'cat' => $cat1],
            ['id' => $report2Id, 'title' => $title2, 'desc' => $desc2, 'cat' => $cat2],
            ['id' => $report3Id, 'title' => $title3, 'desc' => $desc3, 'cat' => $cat3],
        ];
        
        foreach ($reports as $r) {
            try {
                $result = callAIPriority($r['id'], $r['title'], $r['desc'], $r['cat']);
                if ($result) {
                    echo "    ✓ Analyzed report {$r['id']}: Priority {$result['priority']}/5 - {$result['reason']}\n";
                } else {
                    echo "    ⚠ AI analysis failed for report {$r['id']} (API may not be configured)\n";
                }
            } catch (Exception $e) {
                echo "    ⚠ AI analysis error for report {$r['id']}: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "  ⚠ OpenRouter API key not configured. Skipping AI analysis.\n";
        echo "    To enable AI features, set OPENROUTER_API_KEY in config.php\n";
    }
    
    echo "\n✓ Database seeding completed successfully!\n\n";
    echo "Demo Credentials:\n";
    echo "==================\n";
    echo "Admin:\n";
    echo "  Username: admin_demo\n";
    echo "  Password: DemoPass123!\n\n";
    echo "Municipality Head:\n";
    echo "  Username: muni_demo\n";
    echo "  Password: DemoPass123!\n\n";
    echo "Civilian Users:\n";
    echo "  Username: user1 / Password: DemoPass123!\n";
    echo "  Username: user2 / Password: DemoPass123!\n\n";
    
} catch (Exception $e) {
    echo "\n✗ Error during seeding: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

if (!$isCLI) {
    echo "</pre></body></html>";
}

