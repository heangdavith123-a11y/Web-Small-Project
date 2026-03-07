<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

    $host="localhost";
    $user = "root";
    $password = "";
    $database ="booksalsedb";

    $conn = new mysqli($host, $user, $password, $database);

    if($conn -> connect_error){
        die("Connection failed:" . $conn->connect_error);
    }else{
        // Only redirect if this file is accessed directly, not when included
        if (basename($_SERVER['PHP_SELF']) == 'ConnDB.php') {
            header("Location: Main.php");
            exit;
        }
        // If included by other files, just continue without redirect
    }


?>