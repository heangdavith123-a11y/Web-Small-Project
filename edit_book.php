<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - Education Book Shop</title>
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

    $book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $book = null;

    // Handle Update
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = $_POST['title'];
        $isbn = $_POST['isbn'];
        $category = $_POST['category'];
        $page_number = $_POST['page_number'];
        $unit_price = $_POST['unit_price'];
        $stock_qty = $_POST['stock_qty'];
        $id = $_POST['book_id'];

        $stmt = $conn->prepare("UPDATE books SET title=?, isbn=?, category=?, page_number=?, unit_price=?, stock_qty=? WHERE book_id=?");
        $stmt->bind_param("sssddii", $title, $isbn, $category, $page_number, $unit_price, $stock_qty, $id);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Book updated successfully!</div>";
            // Update local variable to show new values in form
            $book_id = $id; 
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }

    // Fetch existing data (runs on load and after update)
    if ($book_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $book = $result->fetch_assoc();
        } else {
            echo "<div class='container'><div class='alert alert-danger'>Book not found.</div><a href='inventory.php' class='btn btn-secondary'>Back</a></div>";
            exit;
        }
        $stmt->close();
    } else {
        header("Location: inventory.php");
        exit;
    }
    ?>

    <div class="container">
        <div class="page-header">
            <h1>Edit Book</h1>
            <a href="inventory.php" class="btn btn-secondary">← Back to Inventory</a>
        </div>

        <?php if (isset($message)) echo $message; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $book_id; ?>">
            <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
            
            <div class="form-group">
                <label for="title">Book Title</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($book['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="isbn">ISBN</label>
                <input type="text" id="isbn" name="isbn" class="form-control" value="<?php echo htmlspecialchars($book['isbn']); ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" class="form-control" value="<?php echo htmlspecialchars($book['category']); ?>" required>
            </div>

            <div class="form-group">
                <label for="page_number">Page Number</label>
                <input type="number" id="page_number" name="page_number" class="form-control" min="1" value="<?php echo htmlspecialchars($book['page_number']); ?>" required>
            </div>

            <div class="form-group">
                <label for="unit_price">Unit Price ($)</label>
                <input type="number" id="unit_price" name="unit_price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($book['unit_price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="stock_qty">Stock Quantity</label>
                <input type="number" id="stock_qty" name="stock_qty" class="form-control" min="0" value="<?php echo htmlspecialchars(isset($book['stock_qty']) ? $book['stock_qty'] : 0); ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Update Book</button>
        </form>
    </div>
</body>
</html>