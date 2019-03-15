<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

App::$db = @new mysqli(DBHOST, DBUSER, DBPASS, DB);

if (mysqli_connect_errno()) {
    echo "Connection to the database failed.";
    exit();
}
?>