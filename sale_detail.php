<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Detail - Khmer Book Shop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="print.css" media="print">
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: Login.php");
        exit;
    }

    include 'ConnDB.php';

    // Check if viewing book details or sale receipt
    $sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
    $book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($sale_id > 0) {
        // Show sale receipt
        $query = "SELECT * FROM sales WHERE sale_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $sale_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            header("Location: sales_history.php");
            exit;
        }

        $sale = $result->fetch_assoc();
        $stmt->close();

        // Query to get sale items
        $query = "SELECT sd.*, b.title, b.isbn, b.category
                  FROM salse_details sd
                  JOIN books b ON sd.book_id = b.book_id
                  WHERE sd.sale_id = ? AND b.is_deleted = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $sale_id);
        $stmt->execute();
        $items_result = $stmt->get_result();

        if ($items_result->num_rows == 0) {
            header("Location: sales_history.php");
            exit;
        }

        $items = [];
        $subtotal = 0;
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
            $subtotal += $item['qty'] * $item['unit_price'];
        }

        $tax_rate = 0.1; // 10% tax
        $tax_amount = $subtotal * $tax_rate;
        $grand_total = $subtotal + $tax_amount;

        $stmt->close();
        $conn->close();
    } elseif ($book_id > 0) {
        // Show book details (original functionality)
        $query = "SELECT * FROM books WHERE book_id = ? AND is_deleted = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            header("Location: inventory.php");
            exit;
        }

        $book = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
    } else {
        // No valid parameter
        header("Location: inventory.php");
        exit;
    }

    if ($sale_id > 0) {
        // Display sale receipt
        ?>

        <div class="container">
            <div class="page-header no-print">
                <h1>Sale Receipt</h1>
                <div>
                    <button class="btn btn-success" onclick="window.print()">Print Receipt</button>
                    <a href="sales_history.php" class="btn btn-secondary">← Back to Sales History</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h2>Education Book Shop</h2>
                        <p>Sale ID: #<?php echo $sale['sale_id']; ?></p>
                    </div>
                    <div>
                        <p><strong>Date:</strong> <?php echo date('M j, Y H:i', strtotime($sale['sale_date'])); ?></p>
                        <p><strong>Staff:</strong> <?php echo htmlspecialchars($sale['staff_name']); ?></p>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>ISBN</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['title']); ?></td>
                            <td><?php echo htmlspecialchars($item['isbn']); ?></td>
                            <td><?php echo $item['qty']; ?></td>
                            <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td>$<?php echo number_format($item['qty'] * $item['unit_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold;">
                            <td colspan="4" style="text-align: right;">Subtotal</td>
                            <td>$<?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" style="text-align: right;">Tax (10%)</td>
                            <td>$<?php echo number_format($tax_amount, 2); ?></td>
                        </tr>
                        <tr style="font-weight: bold; font-size: 1.2em; background-color: #f8f9fa;">
                            <td colspan="4" style="text-align: right;">Grand Total</td>
                            <td>$<?php echo number_format($grand_total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    <?php
    } elseif ($book_id > 0) {
        // Display book details
        ?>

        <div class="container">
            <div class="page-header no-print">
                <h1>Book Details</h1>
                <a href="inventory.php" class="btn btn-secondary">← Back to Inventory</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                </div>
                <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h3>Book Information</h3>
                        <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
                        <p><strong>Pages:</strong> <?php echo htmlspecialchars($book['page_number']); ?></p>
                    </div>
                    <div>
                        <h3>Pricing & Status</h3>
                        <p><strong>Unit Price:</strong> <span style="font-size: 1.2em; color: var(--danger-color);">$<?php echo number_format($book['unit_price'], 2); ?></span></p>
                        <p><strong>Status:</strong> <span style="color: var(--success-color); font-weight: bold;">Available</span></p>
                        <p><strong>Added on:</strong> <?php echo date('M j, Y', strtotime($book['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="no-print" style="text-align: center; margin-top: 20px;">
                 <button class="btn btn-success" onclick="window.print()">Print Details</button>
            </div>
        </div>

    <?php
    }
    ?>
    <script>
        // To set the title dynamically
        document.title = '<?php echo $sale_id > 0 ? "Sale #" . $sale_id : "Book: " . htmlspecialchars($book['title']); ?> - Education Book Shop';
    </script>
</body>
</html>