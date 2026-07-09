<?php
/**
 * Storage Symlink Setup Script
 * 
 * Creates the symlink: public/storage -> storage/app/public
 * This is required for Laravel to serve files from the public disk
 * 
 * Run: php setup-storage-link.php
 */

$baseDir = __DIR__;
$storageLink = $baseDir . '/public/storage';
$storageTarget = $baseDir . '/storage/app/public';

echo "🔧 Laravel Storage Symlink Setup\n";
echo "================================\n\n";

// Check if symlink already exists
if (is_link($storageLink)) {
    echo "✅ Symlink already exists!\n";
    echo "   Link: $storageLink\n";
    echo "   Target: " . readlink($storageLink) . "\n";
    exit(0);
}

// Check if target directory exists
if (!is_dir($storageTarget)) {
    echo "❌ ERROR: Storage target directory does not exist!\n";
    echo "   Expected: $storageTarget\n";
    exit(1);
}

// Try to create symlink
echo "📦 Creating symlink...\n";
echo "   Link: $storageLink\n";
echo "   Target: $storageTarget\n\n";

try {
    // Remove existing file/directory if it exists (but is not a symlink)
    if (is_dir($storageLink) || is_file($storageLink)) {
        echo "⚠️  Removing existing file/directory at $storageLink\n";
        if (is_dir($storageLink) && !is_link($storageLink)) {
            // It's a real directory, not safe to remove
            echo "❌ ERROR: Real directory exists at $storageLink\n";
            echo "   Please manually remove it first or backup your data\n";
            exit(1);
        }
        unlink($storageLink);
    }
    
    // Create symlink
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows: Use mklink command
        $command = sprintf('mklink /D "%s" "%s"', $storageLink, $storageTarget);
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            echo "❌ ERROR: Failed to create symlink (Windows)\n";
            echo "   Command: $command\n";
            echo "   Output: " . implode("\n", $output) . "\n";
            echo "   Note: Try running this script as Administrator\n";
            exit(1);
        }
    } else {
        // Linux/Mac: Use symlink function
        if (!symlink($storageTarget, $storageLink)) {
            echo "❌ ERROR: Failed to create symlink (Linux/Mac)\n";
            echo "   Try running: sudo php setup-storage-link.php\n";
            exit(1);
        }
    }
    
    // Verify symlink was created
    if (!is_link($storageLink)) {
        echo "❌ ERROR: Symlink creation failed (unknown reason)\n";
        exit(1);
    }
    
    echo "✅ Symlink created successfully!\n\n";
    echo "📋 Verification:\n";
    echo "   Link: $storageLink\n";
    echo "   Target: " . readlink($storageLink) . "\n\n";
    
    echo "✨ Storage is now ready for file serving!\n";
    echo "   Images will be accessible at: http://127.0.0.1:8000/storage/menu-items/...\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
