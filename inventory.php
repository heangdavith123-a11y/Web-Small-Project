<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Inventory - Education Book Shop</title>
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

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if ($search) {
        $query = "SELECT * FROM books WHERE is_deleted = 0 AND (title LIKE ? OR isbn LIKE ?) ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $searchParam = "%" . $search . "%";
        $stmt->bind_param("ss", $searchParam, $searchParam);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $query = "SELECT * FROM books WHERE is_deleted = 0 ORDER BY created_at DESC";
        $result = $conn->query($query);
    }

    if (!$result) {
        die("Database query failed: " . $conn->error);
    }

    // Handle AJAX search request
    if (isset($_GET['ajax'])) {
        $totalValue = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $stock_qty = isset($row['stock_qty']) ? $row['stock_qty'] : 0;
                $totalValue += $row['unit_price'] * $stock_qty;
                $stock_display = $stock_qty <= 5 ? "<span style='color: var(--danger-color); font-weight: bold;'>$stock_qty</span>" : $stock_qty;
                echo "<tr>
                        <td>" . htmlspecialchars($row['title']) . "</td>
                        <td>" . htmlspecialchars($row['isbn']) . "</td>
                        <td>" . htmlspecialchars($row['category']) . "</td>
                        <td>" . htmlspecialchars($row['page_number']) . "</td>
                        <td>$" . number_format($row['unit_price'], 2) . "</td>
                        <td>" . $stock_display . "</td>
                        <td>" . date('M j, Y', strtotime($row['created_at'])) . "</td>
                        <td>
                            <a href='sale_detail.php?id=" . $row['book_id'] . "' class='btn btn-sm btn-primary'>View</a>
                            <a href='edit_book.php?id=" . $row['book_id'] . "' class='btn btn-sm btn-secondary'>Edit</a>
                            <a href='Delete.php?id=" . $row['book_id'] . "' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to delete this book?')\">Delete</a>
                        </td>
                      </tr>";
            }
            echo "<tr style='background-color: #f8f9fa; font-weight: bold;'>
                    <td colspan='5'>Total Inventory Value:</td>
                    <td>$" . number_format($totalValue, 2) . "</td>
                    <td colspan='2'>" . $result->num_rows . " book titles</td>
                  </tr>";
        } else {
            echo "<tr><td colspan='8' style='text-align: center; padding: 20px;'>No books available in inventory. <a href='add_book.php'>Add a book</a> to get started.</td></tr>";
        }
        exit;
    }
    ?>

    <div class="container">
        <div class="page-header">
            <h1>Book Inventory</h1>
            <div>
                <a href="add_book.php" class="btn btn-success">Add New Book</a>
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <div style="display: flex; gap: 10px;">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Title or ISBN..." value="<?php echo htmlspecialchars($search); ?>" style="max-width: 300px;">
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>ISBN</th>
                    <th>Category</th>
                    <th>Pages</th>
                    <th>Unit Price</th>
                    <th>Stock Qty</th>
                    <th>Date Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                <?php
                $totalValue = 0;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $stock_qty = isset($row['stock_qty']) ? $row['stock_qty'] : 0;
                        $totalValue += $row['unit_price'] * $stock_qty;
                        $stock_display = $stock_qty <= 5 ? "<span style='color: var(--danger-color); font-weight: bold;'>$stock_qty</span>" : $stock_qty;
                        echo "<tr>
                                <td>" . htmlspecialchars($row['title']) . "</td>
                                <td>" . htmlspecialchars($row['isbn']) . "</td>
                                <td>" . htmlspecialchars($row['category']) . "</td>
                                <td>" . htmlspecialchars($row['page_number']) . "</td>
                                <td>$" . number_format($row['unit_price'], 2) . "</td>
                                <td>" . $stock_display . "</td>
                                <td>" . date('M j, Y', strtotime($row['created_at'])) . "</td>
                                <td>
                                    <a href='sale_detail.php?id=" . $row['book_id'] . "' class='btn btn-sm btn-primary'>View</a>
                                    <a href='edit_book.php?id=" . $row['book_id'] . "' class='btn btn-sm btn-secondary'>Edit</a>
                                    <a href='Delete.php?id=" . $row['book_id'] . "' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to delete this book?')\">Delete</a>
                                </td>
                              </tr>";
                    }
                    echo "<tr style='background-color: #f8f9fa; font-weight: bold;'>
                            <td colspan='5'>Total Inventory Value:</td>
                            <td>$" . number_format($totalValue, 2) . "</td>
                            <td colspan='2'>" . $result->num_rows . " book titles</td>
                          </tr>";
                } else {
                    echo "<tr><td colspan='8' style='text-align: center; padding: 20px;'>No books available in inventory. <a href='add_book.php'>Add a book</a> to get started.</td></tr>";
                }

                $result->free();
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
    <script>
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('inventoryTableBody');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value;
            fetch('inventory.php?ajax=1&search=' + encodeURIComponent(searchTerm))
                .then(response => response.text())
                .then(data => {
                    tableBody.innerHTML = data;
                });
        });
    </script>
</body>
</html>