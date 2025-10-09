<?php
// Simple upload configuration that works regardless of .htaccess
// This sets the PHP ini values directly in code

// Increase upload limits
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M'); 
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// These might work depending on server config
@ini_set('upload_max_filesize', '100M');
@ini_set('post_max_size', '100M');
@ini_set('max_execution_time', 300);
@ini_set('memory_limit', '256M');
?>