<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function updateDatabaseSecurity($directory) {
    $log_file = __DIR__ . '/db_security_update_verbose.log';
    file_put_contents($log_file, "\n--- Starting database security update in: $directory ---\n", FILE_APPEND);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $phpFiles = new RegexIterator($iterator, '/\.php$/');

    foreach ($phpFiles as $file) {
        if ($file->isReadable()) {
            $filepath = $file->getPathname();
            $content = file_get_contents($filepath);
            
            // Verbose logging of file processing
            file_put_contents($log_file, "Processing file: $filepath\n", FILE_APPEND);

            // Detect query methods
            $query_methods = [
                '/\$mysqli\s*->\s*query\s*\(\s*(\$[a-zA-Z0-9_]+)\s*\)\s*or\s*die/is',
                '/\$mysqli\s*->\s*query\s*\(\s*[\'"]([^\'"]*)[\'"]\s*\)\s*or\s*die/is'
            ];

            $modified = false;
            foreach ($query_methods as $pattern) {
                if (preg_match($pattern, $content)) {
                    file_put_contents($log_file, "Found potential query vulnerability in: $filepath\n", FILE_APPEND);
                    
                    $new_content = preg_replace_callback(
                        $pattern, 
                        function($matches) use ($filepath) {
                            return "try {\n" .
                                   "    \$stmt = \$mysqli->prepare({$matches[1]});\n" .
                                   "    \$stmt->execute();\n" .
                                   "    \$result = \$stmt->get_result();\n" .
                                   "} catch (Exception \$e) {\n" .
                                   "    error_log('Database Error in " . basename($filepath) . ": ' . \$e->getMessage());\n" .
                                   "    header('HTTP/1.1 500 Internal Server Error');\n" .
                                   "    exit;\n" .
                                   "}";
                        }, 
                        $content
                    );

                    file_put_contents($filepath, $new_content);
                    file_put_contents($log_file, "Updated security in: $filepath\n", FILE_APPEND);
                    $modified = true;
                }
            }

            if (!$modified) {
                file_put_contents($log_file, "No changes made to: $filepath\n", FILE_APPEND);
            }
        }
    }
}

// Specify directories to update
$base_dir = __DIR__;
$directories = [
    $base_dir . '/user',
    $base_dir . '/admin'
];

foreach ($directories as $directory) {
    updateDatabaseSecurity($directory);
}

echo "Database security update complete. Check db_security_update_verbose.log for details.\n";
?>
