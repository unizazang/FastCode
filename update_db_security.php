<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function updateDatabaseSecurity($directory) {
    $log_file = __DIR__ . '/db_security_update.log';
    file_put_contents($log_file, "Starting database security update in: $directory\n", FILE_APPEND);

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $phpFiles = new RegexIterator($iterator, '/\.php$/');

        foreach ($phpFiles as $file) {
            if ($file->isReadable()) {
                $content = file_get_contents($file->getPathname());
                $modified = false;

                // Patterns for direct query calls with error handling
                $queryPatterns = [
                    '/\$mysqli\s*->\s*query\(\s*(\$[a-zA-Z0-9_]+)\s*\)\s*or\s*die\s*\(\s*[\'"].*?[\'"]\s*\);/is',
                    '/\$mysqli\s*->\s*query\(\s*[\'"]([^\'"]*)[\'"]\s*\)\s*or\s*die\s*\(\s*[\'"].*?[\'"]\s*\);/is'
                ];

                $queryReplacements = [
                    function($matches) use ($file) {
                        return "try {
    \$stmt = \$mysqli->prepare({$matches[1]});
    \$stmt->execute();
    \$result = \$stmt->get_result();
} catch (Exception \$e) {
    error_log('Database Error in " . basename($file->getPathname()) . ": ' . \$e->getMessage());
    // Handle error gracefully
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}";
                    },
                    function($matches) use ($file) {
                        return "try {
    \$stmt = \$mysqli->prepare('{$matches[1]}');
    \$stmt->execute();
    \$result = \$stmt->get_result();
} catch (Exception \$e) {
    error_log('Database Error in " . basename($file->getPathname()) . ": ' . \$e->getMessage());
    // Handle error gracefully
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}";
                    }
                ];

                $newContent = preg_replace_callback($queryPatterns, $queryReplacements, $content, -1, $count);
                
                if ($count > 0) {
                    $modified = true;
                    file_put_contents($file->getPathname(), $newContent);
                    file_put_contents($log_file, "Updated database security in: " . $file->getPathname() . "\n", FILE_APPEND);
                    echo "Updated database security in: " . $file->getPathname() . "\n";
                }
            }
        }
    } catch (Exception $e) {
        file_put_contents($log_file, "Error processing directory $directory: " . $e->getMessage() . "\n", FILE_APPEND);
        echo "Error processing directory $directory: " . $e->getMessage() . "\n";
    }
}

// Specify directories to update
$base_dir = __DIR__;
$directories = [
    $base_dir . '/user',
    $base_dir . '/admin'
];

foreach ($directories as $directory) {
    echo "Processing directory: $directory\n";
    updateDatabaseSecurity($directory);
}

echo "Database security update complete. Check db_security_update.log for details.\n";
?>
