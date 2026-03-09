<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - Education Book Shop</title>
    <link rel="stylesheet" href="style.css">
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
    <div class="container">
        <div class="page-header">
            <h1>Sales History</h1>
            <div>
                <a href="create_sale.php" class="btn btn-success">Create New Sale</a>
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>

        <div class="dashboard-layout">
            <div class="sidebar">
                <h2 style="margin-top: 0; margin-bottom: 20px;">Quick Actions</h2>
                <div class="dashboard-menu">
                    <a href="index.php" class="dashboard-item">
                        <span>&#128200;<br>Dashboard</span>
                    </a>
                    <a href="add_book.php" class="dashboard-item">
                        <span>&#43;<br>Add New Book</span>
                    </a>
                    <a href="inventory.php" class="dashboard-item">
                        <span>&#128218;<br>Book Inventory</span>
                    </a>
                    <a href="logout.php" class="dashboard-item logout">
                        <span>&#10162;<br>Logout</span>
                    </a>
                </div>
            </div>

            <div class="main-content">
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
                                            <a href='sale_detail.php?sale_id=" . $row['sale_id'] . "' class='btn btn-sm btn-primary'>Invoice</a>
                                        </td>
                                      </tr>";
                            }
                            echo "<tr style='background-color: #f8f9fa; font-weight: bold;'>
                                    <td colspan='4'>Total Sales:</td>
                                    <td>$" . number_format($totalSales, 2) . "</td>
                                    <td>" . $result->num_rows . " sale(s)</td>
                                  </tr>";
                        } else {
                            echo "<tr><td colspan='6' style='text-align: center; padding: 20px;'>No sales found. <a href='create_sale.php'>Create your first sale</a>.</td></tr>";
                        }

                        $result->free();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>