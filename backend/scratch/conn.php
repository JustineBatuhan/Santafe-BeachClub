<?php
$conn = @new mysqli('127.0.0.1', 'root', '', '', 3307);
if ($conn->connect_error) {
    echo 'Error: ' . $conn->connect_error;
} else {
    echo 'Connected ok';
}
