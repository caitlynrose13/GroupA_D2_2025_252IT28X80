<?php
// Router file for PHP built-in server
// This ensures query parameters are handled correctly

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

// Set the query string if it exists
if ($query) {
    $_SERVER['QUERY_STRING'] = $query;
    parse_str($query, $_GET);
}

// Check if the requested file exists
$file = __DIR__ . $path;

// If it's a directory, try index.php
if (is_dir($file)) {
    $file .= '/index.php';
}

// If file exists and is a PHP file, include it
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    include $file;
    return true;
}

// If file exists and is not PHP, return false to let server handle it
if (file_exists($file)) {
    return false;
}

// File not found
http_response_code(404);
echo "404 Not Found";
return true;
