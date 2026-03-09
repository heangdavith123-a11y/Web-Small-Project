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
            // Check stock
            if ((isset($book['stock_qty']) ? $book['stock_qty'] : 0) >= $quantity) {
                $_SESSION['cart'][$book_id] = [
                    'title' => $book['title'],
                    'isbn' => $book['isbn'],
                    'unit_price' => $book['unit_price'],
                    'quantity' => $quantity
                ];
            } else {
                $error = "Not enough stock for '" . htmlspecialchars($book['title']) . "'. Only " . (isset($book['stock_qty']) ? $book['stock_qty'] : 0) . " available.";
            }
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
    $_SESSION['staff_name'] = $staff_name;

    $conn->begin_transaction(); // Use a transaction

    try {
        // Check stock for all items in cart before processing
        foreach ($_SESSION['cart'] as $book_id => $item) {
            $stmt_stock = $conn->prepare("SELECT title, stock_qty FROM books WHERE book_id = ? FOR UPDATE"); // Lock the row
            $stmt_stock->bind_param("i", $book_id);
            $stmt_stock->execute();
            $result_stock = $stmt_stock->get_result();
            $book_stock = $result_stock->fetch_assoc();
            $stmt_stock->close();

            if (!$book_stock || (isset($book_stock['stock_qty']) ? $book_stock['stock_qty'] : 0) < $item['quantity']) {
                throw new Exception("Not enough stock for '" . htmlspecialchars($item['title']) . "'. Only " . (isset($book_stock['stock_qty']) ? $book_stock['stock_qty'] : 0) . " available.");
            }
        }

        // Calculate total
        $total_amount = 0;
        foreach ($_SESSION['cart'] as $book_id => $item) {
            $total_amount += $item['quantity'] * $item['unit_price'];
        }

        // Insert sale record
        $stmt_sale = $conn->prepare("INSERT INTO sales (sale_date, staff_name, total_amount) VALUES (NOW(), ?, ?)");
        $stmt_sale->bind_param("sd", $staff_name, $total_amount);
        $stmt_sale->execute();
        $sale_id = $conn->insert_id;
        $stmt_sale->close();

        // Insert sale details and UPDATE STOCK
        $stmt_details = $conn->prepare("INSERT INTO salse_details (sale_id, book_id, qty, unit_price, amount) VALUES (?, ?, ?, ?, ?)");
        $stmt_stock_update = $conn->prepare("UPDATE books SET stock_qty = stock_qty - ? WHERE book_id = ?");

        foreach ($_SESSION['cart'] as $book_id => $item) {
            $amount = $item['quantity'] * $item['unit_price'];
            $stmt_details->bind_param("iiidd", $sale_id, $book_id, $item['quantity'], $item['unit_price'], $amount);
            $stmt_details->execute();

            $stmt_stock_update->bind_param("ii", $item['quantity'], $book_id);
            $stmt_stock_update->execute();
        }
        $stmt_details->close();
        $stmt_stock_update->close();

        $conn->commit(); // Commit transaction

        $_SESSION['cart'] = [];
        header("Location: sale_detail.php?sale_id=" . $sale_id);
        exit;
    } catch (Exception $e) {
        $conn->rollback(); // Rollback on error
        $error = $e->getMessage();
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
    <title>Create Sale - Education Book Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>Create New Sale</h1>
            <a href="sales_history.php" class="btn btn-secondary">← Back to Sales History</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="" class="form-inline">
                <div class="form-group" style="flex-grow: 1; margin-right: 10px;">
                    <label for="staff_name" style="margin-right: 10px;">Staff Name:</label>
                    <input type="text" id="staff_name" name="staff_name" class="form-control" value="<?php echo isset($_SESSION['staff_name']) ? htmlspecialchars($_SESSION['staff_name']) : 'Staff'; ?>" required>
                </div>
                <button type="submit" name="update_staff" class="btn btn-secondary">Update</button>
            </form>
        </div>

        <div class="sale-container">
            <div class="card">
                <div class="card-header">Available Books</div>
                <table>
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Price</th>
                            <th style="width: 180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($books_result->num_rows > 0): ?>
                        <?php while ($book = $books_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                    <br>
                                    <small>ISBN: <?php echo htmlspecialchars($book['isbn']); ?> | Stock: <?php echo isset($book['stock_qty']) ? $book['stock_qty'] : 0; ?></small>
                                </td>
                                <td>$<?php echo number_format($book['unit_price'], 2); ?></td>
                                <td>
                                    <?php
                                    $stock = isset($book['stock_qty']) ? $book['stock_qty'] : 0;
                                    if ($stock > 0) {
                                    ?>
                                        <form method="POST" action="" style="display: flex;">
                                            <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                            <input type="number" name="quantity" class="form-control" min="1" max="<?php echo $stock; ?>" value="1" required style="width: 60px; margin-right: 5px;">
                                            <button type="submit" name="add_to_cart" class="btn btn-sm btn-success">Add</button>
                                        </form>
                                    <?php
                                    } else {
                                        echo "<span style='color: var(--danger-color);'>Out of Stock</span>";
                                    } ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center; padding: 20px;">No books available in inventory.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($_SESSION['cart'])): ?>
                <div class="card">
                    <div class="card-header">Current Sale</div>
                    <?php
                    $cart_total = 0;
                    foreach ($_SESSION['cart'] as $book_id => $item):
                        $item_total = $item['quantity'] * $item['unit_price'];
                        $cart_total += $item_total;
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee;">
                            <div>
                                <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
                                <small>Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['unit_price'], 2); ?></small>
                            </div>
                            <div>
                                <strong>$<?php echo number_format($item_total, 2); ?></strong>
                                <a href="?remove=<?php echo $book_id; ?>" style="color: var(--danger-color); margin-left: 10px; font-size: 0.9em;">Remove</a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="padding: 15px; text-align: right;">
                        <p>Subtotal: <strong>$<?php echo number_format($cart_total, 2); ?></strong></p>
                        <p>Tax (10%): <strong>$<?php echo number_format($cart_total * 0.1, 2); ?></strong></p>
                        <hr>
                        <p style="font-size: 1.2em;">Grand Total: <strong>$<?php echo number_format($cart_total * 1.1, 2); ?></strong></p>
                    </div>

                    <form method="POST" action="" style="padding: 0 20px 20px 20px;">
                        <input type="hidden" name="staff_name" value="<?php echo isset($_SESSION['staff_name']) ? htmlspecialchars($_SESSION['staff_name']) : 'Staff'; ?>">
                        <button type="submit" name="process_sale" class="btn btn-primary" style="width: 100%;">Process Sale</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    $books_result->free();
    $conn->close();
    ?>
</body>
</html>