<?php
/**
 * Migration Runner Script
 * Runs all pending database migrations
 */

require_once 'db.php';

// ANSI color codes for terminal output
$colors = [
    'reset' => "\033[0m",
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'bold' => "\033[1m"
];

function colorize($text, $color, $bold = false) {
    global $colors;
    $prefix = $bold ? $colors['bold'] : '';
    return $prefix . $colors[$color] . $text . $colors['reset'];
}

echo colorize("╔═══════════════════════════════════════════════╗\n", 'blue', true);
echo colorize("║   FEZA LOGISTICS - DATABASE MIGRATION TOOL   ║\n", 'blue', true);
echo colorize("╚═══════════════════════════════════════════════╝\n", 'blue', true);
echo "\n";

// Create migrations tracking table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `migration_file` varchar(255) NOT NULL,
            `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_migration` (`migration_file`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo colorize("✓", 'green') . " Schema migrations table ready\n";
} catch (PDOException $e) {
    echo colorize("✗", 'red') . " Failed to create schema_migrations table: " . $e->getMessage() . "\n";
    exit(1);
}

// Get list of applied migrations
$appliedStmt = $pdo->query("SELECT migration_file FROM schema_migrations ORDER BY migration_file");
$appliedMigrations = $appliedStmt->fetchAll(PDO::FETCH_COLUMN);

echo colorize("\nApplied migrations: " . count($appliedMigrations), 'blue') . "\n";

// Get list of migration files
$migrationsDir = __DIR__ . '/migrations';
$migrationFiles = glob($migrationsDir . '/*.sql');
sort($migrationFiles);

echo colorize("Available migration files: " . count($migrationFiles), 'blue') . "\n\n";

// Filter pending migrations
$pendingMigrations = [];
foreach ($migrationFiles as $file) {
    $filename = basename($file);
    if (!in_array($filename, $appliedMigrations)) {
        $pendingMigrations[] = $file;
    }
}

if (empty($pendingMigrations)) {
    echo colorize("✓ All migrations are up to date!", 'green', true) . "\n";
    exit(0);
}

echo colorize("Found " . count($pendingMigrations) . " pending migration(s):", 'yellow', true) . "\n";
foreach ($pendingMigrations as $file) {
    echo "  • " . basename($file) . "\n";
}
echo "\n";

// Ask for confirmation
echo "Do you want to apply these migrations? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo colorize("\n✗ Migration cancelled by user\n", 'yellow');
    exit(0);
}

echo "\n" . colorize("Starting migration process...", 'blue', true) . "\n\n";

$successCount = 0;
$failCount = 0;

foreach ($pendingMigrations as $file) {
    $filename = basename($file);
    echo "Applying: " . colorize($filename, 'blue') . "... ";
    
    try {
        // Read migration file
        $sql = file_get_contents($file);
        
        // Remove comments and split into statements
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Execute migration in a transaction
        $pdo->beginTransaction();
        
        // Split by semicolon, but be careful with stored procedures
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Record migration
        $recordStmt = $pdo->prepare("INSERT INTO schema_migrations (migration_file) VALUES (:filename)");
        $recordStmt->execute([':filename' => $filename]);
        
        $pdo->commit();
        
        echo colorize("✓ SUCCESS", 'green', true) . "\n";
        $successCount++;
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo colorize("✗ FAILED", 'red', true) . "\n";
        echo colorize("   Error: " . $e->getMessage(), 'red') . "\n";
        $failCount++;
        
        // Ask if we should continue
        echo "\nContinue with remaining migrations? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'yes') {
            echo colorize("\n✗ Migration process stopped by user\n", 'yellow');
            break;
        }
        echo "\n";
    }
}

// Summary
echo "\n" . colorize("═══════════════════════════════════", 'blue') . "\n";
echo colorize("Migration Summary:", 'blue', true) . "\n";
echo "  " . colorize("✓ Successful: " . $successCount, 'green') . "\n";
echo "  " . colorize("✗ Failed: " . $failCount, 'red') . "\n";
echo colorize("═══════════════════════════════════", 'blue') . "\n\n";

if ($failCount === 0) {
    echo colorize("✓ All migrations completed successfully!", 'green', true) . "\n";
    exit(0);
} else {
    echo colorize("⚠ Some migrations failed. Please check the errors above.", 'yellow', true) . "\n";
    exit(1);
}
