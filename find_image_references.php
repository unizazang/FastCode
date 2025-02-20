<?php
function findImageReferences($directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $files = new RegexIterator($iterator, '/\.(php|html|htm|js)$/');

    $imageReferences = [];

    foreach ($files as $file) {
        if ($file->isReadable()) {
            $filepath = $file->getPathname();
            $content = file_get_contents($filepath);

            // Search for various image path patterns
            $patterns = [
                '/[\'"]([^\'"\s]+\.(?:jpg|jpeg|png|gif|webp))[\'"]/',  // Image file extensions
                '/src\s*=\s*[\'"]([^\'"\s]+)[\'"]/',  // HTML/JS src attributes
                '/background(?:-image)?:\s*url\([\'"]?([^\'"\)]+)[\'"]?\)/',  // CSS background images
            ];

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $imagePath = $match[1];
                        
                        // Check if path contains pdata or might be related to pdata
                        if (stripos($imagePath, 'pdata') !== false || 
                            preg_match('/\d{6,}.*\.(jpg|jpeg|png|gif)/', $imagePath)) {
                            
                            if (!isset($imageReferences[$filepath])) {
                                $imageReferences[$filepath] = [];
                            }
                            $imageReferences[$filepath][] = $imagePath;
                        }
                    }
                }
            }
        }
    }

    return $imageReferences;
}

// Specify directories to search
$directories = [
    __DIR__ . '/admin/coupon',
    __DIR__ . '/admin/board'
];

$allImageReferences = [];

foreach ($directories as $directory) {
    echo "Processing directory: $directory\n";
    $imageRefs = findImageReferences($directory);
    
    if (!empty($imageRefs)) {
        echo "Image references found in directory:\n";
        foreach ($imageRefs as $filepath => $references) {
            echo "File: $filepath\n";
            foreach ($references as $ref) {
                echo "  - $ref\n";
            }
        }
        $allImageReferences = array_merge_recursive($allImageReferences, $imageRefs);
    }
}

if (empty($allImageReferences)) {
    echo "No image references found.\n";
}
?>
