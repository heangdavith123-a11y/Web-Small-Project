<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - Khmer Book Shop</title>
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
        .no-sales {
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 18px;
        }
        .create-sale-btn {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            margin-left: 10px;
        }
        .create-sale-btn:hover {
            background-color: #218838;
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

    // Query to get all sales
    $query = "SELECT s.*, COUNT(sd.book_id) as item_count
              FROM sales s
              LEFT JOIN salse_details sd ON s.sale_id = sd.sale_id
              GROUP BY s.sale_id
              ORDER BY s.sale_date DESC";
    $result = $conn->query($query);

    if (!$result) {
        die("Database query failed: " . $conn->error);
    }
    ?>

    <a href="Main.php" class="back-link">← Back to Dashboard</a>
    <a href="create_sale.php" class="create-sale-btn">Create New Sale</a>

    <h1>Sales History</h1>

    <table>
        <thead>
            <tr>
                <th>Sale ID</th>
                <th>Date</th>
                <th>Staff</th>
                <th>Items</th>
                <th>Total Amount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalSales = 0;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $totalSales += $row['total_amount'];
                    echo "<tr>
                            <td>#" . $row['sale_id'] . "</td>
                            <td>" . date('M j, Y H:i', strtotime($row['sale_date'])) . "</td>
                            <td>" . htmlspecialchars($row['staff_name']) . "</td>
                            <td>" . $row['item_count'] . " item(s)</td>
                            <td>$" . number_format($row['total_amount'], 2) . "</td>
                            <td>
                                <a href='sale_detail.php?sale_id=" . $row['sale_id'] . "' class='view-link'>View Receipt</a>
                                <a href='Delete.php?sale_id=" . $row['sale_id'] . "' class='delete-link' onclick=\"return confirm('Are you sure you want to delete this sale?')\">Delete</a>
                            </td>
                          </tr>";
                }
                echo "<tr class='total-row'>
                        <td colspan='4'><strong>Total Sales:</strong></td>
                        <td><strong>$" . number_format($totalSales, 2) . "</strong></td>
                        <td><strong>" . $result->num_rows . " sale(s)</strong></td>
                      </tr>";
            } else {
                echo "<tr><td colspan='6' class='no-sales'>No sales found. <a href='create_sale.php'>Create your first sale</a>.</td></tr>";
            }

            $result->free();
            $conn->close();
            ?>
        </tbody>
    </table>
</body>
</html>