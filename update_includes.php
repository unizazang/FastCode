<?php
// Script to update include paths in PHP files

function updateIncludePaths($directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $phpFiles = new RegexIterator($iterator, '/\.php$/');

    foreach ($phpFiles as $file) {
        if ($file->isReadable()) {
            $content = file_get_contents($file->getPathname());
            
            // Replace $_SERVER['DOCUMENT_ROOT'] includes
            $patterns = [
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/db\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/user\/head\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/user\/header\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/user\/footer\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/user\/tail\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/head\.php[\'"]\s*;/i',
                '/include\s+\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/inc\/footer\.php[\'"]\s*;/i',
            ];

            $replacements = [
                'include __DIR__ . "/../../inc/db.php";',
                'include __DIR__ . "/../../inc/user/head.php";',
                'include __DIR__ . "/../../inc/user/header.php";',
                'include __DIR__ . "/../../inc/user/footer.php";',
                'include __DIR__ . "/../../inc/user/tail.php";',
                'include __DIR__ . "/../../inc/head.php";',
                'include __DIR__ . "/../../inc/footer.php";',
            ];

            $newContent = preg_replace($patterns, $replacements, $content);

            if ($newContent !== $content) {
                file_put_contents($file->getPathname(), $newContent);
                echo "Updated: " . $file->getPathname() . "\n";
            }
        }
    }
}

// Specify the root directory of your project
$projectRoot = __DIR__;
updateIncludePaths($projectRoot);
echo "Include path update complete.\n";
?>
