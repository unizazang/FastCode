<?php
function updatePhpFiles($directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $phpFiles = new RegexIterator($iterator, '/\.php$/');

    foreach ($phpFiles as $file) {
        if ($file->isReadable()) {
            $content = file_get_contents($file->getPathname());
            $modified = false;

            // Patterns to replace
            $patterns = [
                // Database connection
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/db\.php[\'"]\s*;/i',
                
                // Include paths
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/head\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/common\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/footer\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/foot\.php[\'"]\s*;/i',
                
                // User includes
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/user\/head\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/user\/header\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/user\/footer\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/user\/tail\.php[\'"]\s*;/i',
            ];

            $replacements = [
                'include __DIR__ . "/../../inc/db.php";',
                'include __DIR__ . "/../../inc/head.php";',
                'include __DIR__ . "/../../inc/common.php";',
                'include __DIR__ . "/../../inc/footer.php";',
                'include __DIR__ . "/../../inc/foot.php";',
                'include __DIR__ . "/../../inc/user/head.php";',
                'include __DIR__ . "/../../inc/user/header.php";',
                'include __DIR__ . "/../../inc/user/footer.php";',
                'include __DIR__ . "/../../inc/user/tail.php";',
            ];

            // Replace include paths
            $newContent = preg_replace($patterns, $replacements, $content, -1, $count);

            // Add authentication check if not already present
            if (strpos($newContent, 'if (!isset($_SESSION[\'AUID\'])) {') === false) {
                $authCheck = '
    session_start();
    
    // Check user authentication
    if (!isset($_SESSION[\'AUID\'])) {
        echo "<script>
                alert(\'접근 권한이 없습니다\');
                history.back();
            </script>";
        exit;
    }
';
                // Insert authentication check near the top of the file
                $newContent = preg_replace('/^<\?php\s*/', "<?php\n" . $authCheck, $newContent, 1);
                $modified = true;
            }

            // Replace SQL queries with prepared statements (basic pattern)
            $sqlPatterns = [
                '/\$mysqli\s*->\s*query\(\s*"([^"]+)"\s*\)\s*or\s*die\([\'"][^\'"]*/i',
                '/\$mysqli\s*->\s*query\(\s*\'([^\']+)\'\s*\)\s*or\s*die\([\'"][^\'"]*/i',
            ];

            $sqlReplacements = [
                '$stmt = $mysqli->prepare("$1");
    $stmt->execute();
    $result = $stmt->get_result();',
                '$stmt = $mysqli->prepare(\'$1\');
    $stmt->execute();
    $result = $stmt->get_result();',
            ];

            $newContent = preg_replace($sqlPatterns, $sqlReplacements, $newContent, -1, $count);
            if ($count > 0) {
                $modified = true;
            }

            // Write changes if modified
            if ($modified) {
                file_put_contents($file->getPathname(), $newContent);
                echo "Updated: " . $file->getPathname() . "\n";
            }
        }
    }
}

// Specify the directories to update
$directories = [
    __DIR__ . '/admin/board',
    __DIR__ . '/admin/category',
    __DIR__ . '/admin/coupon',
    __DIR__ . '/admin/lecture'
];

foreach ($directories as $directory) {
    echo "Processing directory: $directory\n";
    updatePhpFiles($directory);
}

echo "Update complete.\n";
?>
