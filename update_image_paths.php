<?php
function updateImagePaths($directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $files = new RegexIterator($iterator, '/\.(php|html|htm)$/');

    $updatedFiles = [];

    foreach ($files as $file) {
        if ($file->isReadable()) {
            $filepath = $file->getPathname();
            $content = file_get_contents($filepath);
            $modified = false;

            // Patterns to replace image paths
            $patterns = [
                // Replace $_SERVER['DOCUMENT_ROOT'] with relative paths
                '/\$_SERVER\[([\'"])DOCUMENT_ROOT\1\]\s*\.\s*[\'"]\/pdata\/([^\'"]*)[\'"]/',
                
                // Replace direct pdata paths that might be problematic
                '/[\'"]\/pdata\/([^\'"]*)[\'"]/',
                
                // Replace absolute paths to pdata
                '/[\'"]([A-Z]:\\\\.*?\\\\pdata\\\\[^\'"]*)[\'"]/'
            ];

            $replacements = [
                // Relative path replacement
                function($matches) {
                    return '"/pdata/' . $matches[2] . '"';
                },
                
                // Keep existing relative paths
                function($matches) {
                    return '"/pdata/' . $matches[1] . '"';
                },
                
                // Convert absolute paths to relative web paths
                function($matches) {
                    // Extract filename from absolute path
                    $filename = basename($matches[1]);
                    return '"/pdata/' . $filename . '"';
                }
            ];

            // Apply replacements
            $newContent = preg_replace_callback($patterns, $replacements, $content, -1, $count);
            
            if ($count > 0) {
                file_put_contents($filepath, $newContent);
                $updatedFiles[] = $filepath;
                echo "Updated image paths in: $filepath\n";
            }
        }
    }

    return $updatedFiles;
}

// Specify directories to update
$directories = [
    __DIR__ . '/admin/coupon',
    __DIR__ . '/admin/board'
];

$allUpdatedFiles = [];

foreach ($directories as $directory) {
    echo "Processing directory: $directory\n";
    $updatedFiles = updateImagePaths($directory);
    $allUpdatedFiles = array_merge($allUpdatedFiles, $updatedFiles);
}

echo "\nTotal files updated: " . count($allUpdatedFiles) . "\n";
echo "Updated Files:\n";
print_r($allUpdatedFiles);
?>
