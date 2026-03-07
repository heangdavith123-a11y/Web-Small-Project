<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khmer Book Shop - Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        h1 {
            color: #333;
        }
        .menu {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 50px;
        }
        .menu-item {
            display: block;
            background-color: #3498db;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            transition: background-color 0.3s;
        }
        .menu-item:hover {
            background-color: #2980b9;
        }
        .logout {
            background-color: #e74c3c;
        }
        .logout:hover {
            background-color: #c0392b;
        }
        .description {
            margin-top: 40px;
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: Login.php");
        exit;
    }
    ?>
    <h1>Education Book Shop - Dashboard</h1>

    <div class="menu">
        <a href="add_book.php" class="menu-item">Add New Book</a>
        <a href="salse.php" class="menu-item">Book Inventory</a>
        <a href="sales_history.php" class="menu-item">Sales Management</a>
        <a href="logout.php" class="menu-item logout">Logout</a>
    </div>

    <div class="description">
        <p>Welcome to the Education Book Shop management system. Use the menu above to manage books, view inventory, and process sales transactions.</p>
    </div>
</body>
</html>