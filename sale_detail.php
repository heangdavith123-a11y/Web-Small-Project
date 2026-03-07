<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Detail - Khmer Book Shop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .invoice-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
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
        .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-section {
            flex: 1;
        }
        .info-section h3 {
            margin-top: 0;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        .total-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .grand-total {
            background-color: #d4edda;
            font-size: 18px;
        }
        .print-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
        .print-btn:hover {
            background-color: #218838;
        }
        @media print {
            .back-link, .print-btn {
                display: none;
            }
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
            header("Location: salse.php");
            exit;
        }

        $book = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
    } else {
        // No valid parameter
        header("Location: salse.php");
        exit;
    }

    if ($sale_id > 0) {
        // Display sale receipt
        ?>

        <a href="sales_history.php" class="back-link">← Back to Sales History</a>

        <div class="invoice-header">
            <div class="invoice-title">SALE RECEIPT</div>
            <p>Khmer Book Shop</p>
            <p><strong>Sale ID:</strong> #<?php echo $sale['sale_id']; ?></p>
        </div>

        <div class="order-info">
            <div class="info-section">
                <h3>Sale Information</h3>
                <p><strong>Date:</strong> <?php echo date('M j, Y H:i', strtotime($sale['sale_date'])); ?></p>
                <p><strong>Staff:</strong> <?php echo htmlspecialchars($sale['staff_name']); ?></p>
                <p><strong>Items:</strong> <?php echo count($items); ?> book(s)</p>
            </div>
            <div class="info-section">
                <h3>Payment Summary</h3>
                <p><strong>Subtotal:</strong> $<?php echo number_format($subtotal, 2); ?></p>
                <p><strong>Tax (10%):</strong> $<?php echo number_format($tax_amount, 2); ?></p>
                <p><strong><span style="font-size: 18px;">Total: $<?php echo number_format($grand_total, 2); ?></span></strong></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>ISBN</th>
                    <th>Category</th>
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
                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                    <td><?php echo $item['qty']; ?></td>
                    <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td>$<?php echo number_format($item['qty'] * $item['unit_price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="5"><strong>Subtotal</strong></td>
                    <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                </tr>
                <tr class="total-row">
                    <td colspan="5"><strong>Tax (10%)</strong></td>
                    <td><strong>$<?php echo number_format($tax_amount, 2); ?></strong></td>
                </tr>
                <tr class="grand-total">
                    <td colspan="5"><strong>Grand Total</strong></td>
                    <td><strong>$<?php echo number_format($grand_total, 2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 30px;">
            <button class="print-btn" onclick="window.print()">Print Receipt</button>
            <a href="sales_history.php" style="display: inline-block; margin-left: 10px; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px;">Back to Sales History</a>
        </div>

    <?php
    } elseif ($book_id > 0) {
        // Display book details
        ?>

        <a href="salse.php" class="back-link">← Back to Inventory</a>

        <div class="invoice-header">
            <div class="invoice-title">BOOK DETAILS</div>
            <p>Khmer Book Shop</p>
        </div>

        <div class="order-info">
            <div class="info-section">
                <h3>Book Information</h3>
                <p><strong>Title:</strong> <?php echo htmlspecialchars($book['title']); ?></p>
                <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
                <p><strong>Pages:</strong> <?php echo htmlspecialchars($book['page_number']); ?></p>
            </div>
            <div class="info-section">
                <h3>Pricing & Status</h3>
                <p><strong>Unit Price:</strong> $<?php echo number_format($book['unit_price'], 2); ?></p>
                <p><strong>Status:</strong> <span style="color: green; font-weight: bold;">Available</span></p>
                <p><strong>Added Date:</strong> <?php echo date('M j, Y', strtotime($book['created_at'])); ?></p>
                <p><strong>Book ID:</strong> <?php echo $book['book_id']; ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Book</td>
                    <td><?php echo htmlspecialchars($book['title']); ?> (ISBN: <?php echo htmlspecialchars($book['isbn']); ?>)</td>
                    <td>1</td>
                    <td>$<?php echo number_format($book['unit_price'], 2); ?></td>
                    <td>$<?php echo number_format($book['unit_price'], 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="4">Subtotal</td>
                    <td>$<?php echo number_format($book['unit_price'], 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="4">Tax (10%)</td>
                    <td>$<?php echo number_format($book['unit_price'] * 0.1, 2); ?></td>
                </tr>
                <tr class="grand-total">
                    <td colspan="4"><strong>Grand Total</strong></td>
                    <td><strong>$<?php echo number_format($book['unit_price'] * 1.1, 2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 30px;">
            <button class="print-btn" onclick="window.print()">Print Details</button>
            <a href="salse.php" style="display: inline-block; margin-left: 10px; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px;">Back to Inventory</a>
        </div>

    <?php
    }
    ?>

</body>
</html>