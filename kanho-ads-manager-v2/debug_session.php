<?php
session_start();

echo "<h2>Session Debug Information</h2>";

echo "<h3>Raw Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>is_logged_in() Test:</h3>";
require_once 'app/Helpers/functions.php';
echo "is_logged_in(): " . (is_logged_in() ? 'TRUE' : 'FALSE') . "<br>";

echo "<h3>Session Checks:</h3>";
echo "isset(\$_SESSION['user']): " . (isset($_SESSION['user']) ? 'TRUE' : 'FALSE') . "<br>";
echo "isset(\$_SESSION['logged_in']): " . (isset($_SESSION['logged_in']) ? 'TRUE' : 'FALSE') . "<br>";

if (isset($_SESSION['user'])) {
    echo "<h3>\$_SESSION['user'] contents:</h3>";
    echo "<pre>";
    print_r($_SESSION['user']);
    echo "</pre>";
}
?>