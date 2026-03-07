<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: Login.php");
    exit;
}

include 'ConnDB.php';

// Handle staff name update
if (isset($_POST['update_staff'])) {
    $_SESSION['staff_name'] = trim($_POST['staff_name']);
}

// Handle adding items to cart
if (isset($_POST['add_to_cart']) && isset($_POST['book_id']) && isset($_POST['quantity'])) {
    $book_id = (int)$_POST['book_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        // Check if book exists and is available
        $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ? AND is_deleted = 0");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $book = $result->fetch_assoc();
            $_SESSION['cart'][$book_id] = [
                'title' => $book['title'],
                'isbn' => $book['isbn'],
                'unit_price' => $book['unit_price'],
                'quantity' => $quantity
            ];
        }
        $stmt->close();
    }
}

// Handle removing items from cart
if (isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    header("Location: create_sale.php");
    exit;
}

// Handle processing the sale
if (isset($_POST['process_sale']) && !empty($_SESSION['cart'])) {
    $staff_name = isset($_POST['staff_name']) ? trim($_POST['staff_name']) : (isset($_SESSION['staff_name']) ? $_SESSION['staff_name'] : 'Staff');

    // Store staff name in session for future use
    $_SESSION['staff_name'] = $staff_name;

    // Calculate total
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['quantity'] * $item['unit_price'];
    }

    // Insert sale record
    $stmt = $conn->prepare("INSERT INTO sales (sale_date, staff_name, total_amount) VALUES (NOW(), ?, ?)");
    $stmt->bind_param("sd", $staff_name, $total_amount);

    if ($stmt->execute()) {
        $sale_id = $conn->insert_id;
        $stmt->close();

        // Insert sale details
        $stmt = $conn->prepare("INSERT INTO salse_details (sale_id, book_id, qty, unit_price, amount) VALUES (?, ?, ?, ?, ?)");
        foreach ($_SESSION['cart'] as $book_id => $item) {
            $amount = $item['quantity'] * $item['unit_price'];
            $stmt->bind_param("iiidd", $sale_id, $book_id, $item['quantity'], $item['unit_price'], $amount);
            $stmt->execute();
        }
        $stmt->close();

        // Clear cart
        $_SESSION['cart'] = [];

        // Redirect to sale receipt
        header("Location: sale_detail.php?sale_id=" . $sale_id);
        exit;
    } else {
        $error = "Error processing sale: " . $stmt->error;
        $stmt->close();
    }
}

// Get all available books
$books_result = $conn->query("SELECT * FROM books WHERE is_deleted = 0 ORDER BY title");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Sale - Khmer Book Shop</title>
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
        .sale-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .book-selection {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .book-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .book-item:last-child {
            border-bottom: none;
        }
        .book-info {
            flex: 1;
        }
        .book-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .book-details {
            color: #666;
            font-size: 14px;
        }
        .book-price {
            font-weight: bold;
            color: #e74c3c;
            margin-right: 20px;
        }
        .quantity-input {
            width: 80px;
            padding: 5px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .add-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .add-btn:hover {
            background-color: #218838;
        }
        .cart-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-total {
            font-weight: bold;
            font-size: 18px;
            color: #e74c3c;
            text-align: right;
            margin-top: 20px;
        }
        .process-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .process-btn:hover {
            background-color: #0056b3;
        }
        .success {
            color: green;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <a href="sales_history.php" class="back-link">← Back to Sales History</a>

    <h1>Create New Sale</h1>

    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="sale-form">
        <form method="POST" action="">
            <div class="form-group">
                <label for="staff_name">Staff Name:</label>
                <input type="text" id="staff_name" name="staff_name" value="<?php echo isset($_SESSION['staff_name']) ? htmlspecialchars($_SESSION['staff_name']) : 'Staff'; ?>" required>
                <button type="submit" name="update_staff" style="margin-top: 10px; padding: 5px 15px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Update Staff Name</button>
            </div>
        </form>
    </div>

    <div class="book-selection">
        <h2>Available Books</h2>
        <?php if ($books_result->num_rows > 0): ?>
            <?php while ($book = $books_result->fetch_assoc()): ?>
                <div class="book-item">
                    <div class="book-info">
                        <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                        <div class="book-details">
                            ISBN: <?php echo htmlspecialchars($book['isbn']); ?> |
                            Category: <?php echo htmlspecialchars($book['category']); ?> |
                            Pages: <?php echo htmlspecialchars($book['page_number']); ?>
                        </div>
                    </div>
                    <div class="book-price">$<?php echo number_format($book['unit_price'], 2); ?></div>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                        <input type="number" name="quantity" class="quantity-input" min="1" value="1" required>
                        <button type="submit" name="add_to_cart" class="add-btn">Add to Cart</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No books available in inventory.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['cart'])): ?>
        <div class="cart-section">
            <h2>Current Sale Items</h2>
            <?php
            $cart_total = 0;
            foreach ($_SESSION['cart'] as $book_id => $item):
                $item_total = $item['quantity'] * $item['unit_price'];
                $cart_total += $item_total;
            ?>
                <div class="cart-item">
                    <div>
                        <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        (ISBN: <?php echo htmlspecialchars($item['isbn']); ?>)<br>
                        Quantity: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['unit_price'], 2); ?>
                    </div>
                    <div>
                        <strong>$<?php echo number_format($item_total, 2); ?></strong>
                        <a href="?remove=<?php echo $book_id; ?>" style="color: #e74c3c; margin-left: 10px;">Remove</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cart-total">
                Total: $<?php echo number_format($cart_total, 2); ?><br>
                Tax (10%): $<?php echo number_format($cart_total * 0.1, 2); ?><br>
                <strong>Grand Total: $<?php echo number_format($cart_total * 1.1, 2); ?></strong>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="staff_name" value="<?php echo isset($_POST['staff_name']) ? htmlspecialchars($_POST['staff_name']) : 'Staff'; ?>">
                <button type="submit" name="process_sale" class="process-btn">Process Sale</button>
            </form>
        </div>
    <?php endif; ?>

    <?php
    $books_result->free();
    $conn->close();
    ?>
</body>
</html>