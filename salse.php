<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - Khmer Book Shop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .back-link {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-link:hover {
            background-color: #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .view-link {
            color: #3498db;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #3498db;
            border-radius: 3px;
        }
        .view-link:hover {
            background-color: #3498db;
            color: white;
        }
        .delete-link {
            color: #e74c3c;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #e74c3c;
            border-radius: 3px;
            margin-left: 5px;
        }
        .delete-link:hover {
            background-color: #e74c3c;
            color: white;
        }
        .total-row {
            background-color: #e9ecef;
            font-weight: bold;
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

    include 'ConnDB.php';

    // Query to get all books for sales
    $query = "SELECT * FROM books WHERE is_deleted = 0 ORDER BY created_at DESC";
    $result = $conn->query($query);

    if (!$result) {
        die("Database query failed: " . $conn->error);
    }
    ?>

    <a href="Main.php" class="back-link">← Back to Dashboard</a>

    <h1>Sales - Book Inventory</h1>

    <table>
        <thead>
            <tr>
                <th>Book Title</th>
                <th>ISBN</th>
                <th>Category</th>
                <th>Pages</th>
                <th>Unit Price</th>
                <th>Date Added</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalValue = 0;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $totalValue += $row['unit_price'];
                    echo "<tr>
                            <td>" . htmlspecialchars($row['title']) . "</td>
                            <td>" . htmlspecialchars($row['isbn']) . "</td>
                            <td>" . htmlspecialchars($row['category']) . "</td>
                            <td>" . htmlspecialchars($row['page_number']) . "</td>
                            <td>$" . number_format($row['unit_price'], 2) . "</td>
                            <td>" . date('M j, Y', strtotime($row['created_at'])) . "</td>
                            <td>
                                <a href='sale_detail.php?id=" . $row['book_id'] . "' class='view-link'>View Details</a>
                                <a href='Delete.php?id=" . $row['book_id'] . "' class='delete-link' onclick=\"return confirm('Are you sure you want to delete this book?')\">Delete</a>
                            </td>
                          </tr>";
                }
                echo "<tr class='total-row'>
                        <td colspan='4'><strong>Total Inventory Value:</strong></td>
                        <td><strong>$" . number_format($totalValue, 2) . "</strong></td>
                        <td colspan='2'><strong>" . $result->num_rows . " books</strong></td>
                      </tr>";
            } else {
                echo "<tr><td colspan='7' style='text-align: center; color: #666;'>No books available in inventory. <a href='add_book.php'>Add books first</a>.</td></tr>";
            }

            $result->free();
            $conn->close();
            ?>
        </tbody>
    </table>
</body>
</html>