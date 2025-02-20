<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'unizazang123');
define('DB_PASS', 'ehflxhtm1!');
define('DB_NAME', 'unizazang123');

// Path Configuration
define('ROOT_PATH', __DIR__ . '/..');
define('INC_PATH', __DIR__);
define('USER_PATH', ROOT_PATH . '/user');
define('ADMIN_PATH', ROOT_PATH . '/admin');

// Function to safely include files
function safe_include($file_path) {
    if (file_exists($file_path)) {
        include $file_path;
    } else {
        error_log("File not found: " . $file_path);
        // Optionally, you can add a fallback or error handling mechanism
    }
}

// Database Connection Function
function db_connect() {
    static $mysqli = null;
    
    if ($mysqli === null) {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($mysqli->connect_errno) {
            error_log('Database Connection Error: ' . $mysqli->connect_error);
            die('Database connection failed');
        }
        
        // Optional: Set character set
        $mysqli->set_charset("utf8mb4");
    }
    
    return $mysqli;
}
?>
