<?php
include 'ConnDB.php';

if ($conn->connect_error) {
    echo 'Connection failed: ' . $conn->connect_error;
} else {
    echo 'Database connected successfully';

    $result = $conn->query('SELECT COUNT(*) as count FROM books');
    if ($result) {
        $row = $result->fetch_assoc();
        echo '<br>Books in inventory: ' . $row['count'];
    }

    $result = $conn->query('SELECT COUNT(*) as count FROM sales');
    if ($result) {
        $row = $result->fetch_assoc();
        echo '<br>Sales completed: ' . $row['count'];
    }

    $conn->close();
}
?>