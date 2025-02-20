<?php
function updateBoardFiles($directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $phpFiles = new RegexIterator($iterator, '/\.php$/');

    foreach ($phpFiles as $file) {
        if ($file->isReadable()) {
            $filepath = $file->getPathname();
            $content = file_get_contents($filepath);
            $modified = false;

            // Replace include paths
            $include_patterns = [
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/([^\'"]*)[\'"]\s*;/i'
            ];

            $include_replacements = [
                'include __DIR__ . "/../../inc/$2";'
            ];

            $new_content = preg_replace($include_patterns, $include_replacements, $content, -1, $count);
            
            if ($count > 0) {
                $modified = true;
            }

            // Add authentication check if not already present
            if (strpos($new_content, 'if (!isset($_SESSION[\'AUID\'])) {') === false) {
                $auth_check = '
    session_start();
    
    // Check user authentication
    if (!isset($_SESSION[\'AUID\'])) {
        echo "<script>
                alert(\'접근 권한이 없습니다\');
                location.href = \'../login.php\';
            </script>";
        exit;
    }
';
                $new_content = preg_replace('/^<\?php\s*/', "<?php\n" . $auth_check, $new_content, 1);
                $modified = true;
            }

            // Convert direct queries to prepared statements
            $query_patterns = [
                '/\$mysqli\s*->\s*query\(\s*(\$[a-zA-Z0-9_]+)\s*\)\s*or\s*die\s*\(\s*[\'"].*?[\'"]\s*\);/is',
                '/\$mysqli\s*->\s*query\(\s*[\'"]([^\'"]*)[\'"]\s*\)\s*or\s*die\s*\(\s*[\'"].*?[\'"]\s*\);/is'
            ];

            $query_replacements = [
                function($matches) use ($filepath) {
                    return "try {
    \$stmt = \$mysqli->prepare({$matches[1]});
    \$stmt->execute();
    \$result = \$stmt->get_result();
} catch (Exception \$e) {
    error_log('Database Error in " . basename($filepath) . ": ' . \$e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}";
                },
                function($matches) use ($filepath) {
                    return "try {
    \$stmt = \$mysqli->prepare('{$matches[1]}');
    \$stmt->execute();
    \$result = \$stmt->get_result();
} catch (Exception \$e) {
    error_log('Database Error in " . basename($filepath) . ": ' . \$e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}";
                }
            ];

            $new_content = preg_replace_callback($query_patterns, $query_replacements, $new_content, -1, $count);
            
            if ($count > 0) {
                $modified = true;
            }

            // Write changes if modified
            if ($modified) {
                file_put_contents($filepath, $new_content);
                echo "Updated: " . $filepath . "\n";
            }
        }
    }
}

// Specify the directory to update
$directory = __DIR__ . '/admin/board';
updateBoardFiles($directory);

echo "Board files update complete.\n";
?>
