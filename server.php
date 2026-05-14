<?php
// Get the port assigned by Railway, default to 8080 if not found
$port = getenv('PORT') ?: 8080;
$host = '0.0.0.0';

echo "Starting server on $host:$port...";

// This command starts the built-in PHP web server
exec("php -S $host:$port index.php");
?>
