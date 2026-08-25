<?php
$db_host = getenv('DATABASE_SERVICE_NAME') ?: 'mysql';
$db_user = getenv('DATABASE_USER') ?: 'developer';
$db_pass = getenv('DATABASE_PASSWORD') ?: 'developer';
$db_name = getenv('DATABASE_NAME') ?: 'quotes';

$link = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$link) {
    http_response_code(500);
    error_log("Error: Unable to connect to database\n");
    die("Database Connection Error");
}

$result = mysqli_query($link, "SELECT quote FROM quotes ORDER BY RAND() LIMIT 1");
if ($row = mysqli_fetch_assoc($result)) {
    echo "<blockquote>" . $row['quote'] . "</blockquote>";
} else {
    echo "No quotes found.";
}
mysqli_close($link);
?>
