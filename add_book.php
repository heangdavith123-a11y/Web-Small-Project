<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book - Education Book Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    include 'ConnDB.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = $_POST['title'];
        $isbn = $_POST['isbn'];
        $category = $_POST['category'];
        $page_number = $_POST['page_number'];
        $unit_price = $_POST['unit_price'];
        $stock_qty = $_POST['stock_qty'];

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO books (title, isbn, category, page_number, unit_price, stock_qty) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddi", $title, $isbn, $category, $page_number, $unit_price, $stock_qty);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>New book added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }

        $stmt->close();
    }
    ?>
    <div class="container">
        <div class="page-header">
            <h1>Add New Book</h1>
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if (isset($message)) echo $message; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="title">Book Title</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="isbn">ISBN</label>
                <input type="text" id="isbn" name="isbn" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="page_number">Page Number</label>
                <input type="number" id="page_number" name="page_number" class="form-control" min="1" required>
            </div>

            <div class="form-group">
                <label for="unit_price">Unit Price ($)</label>
                <input type="number" id="unit_price" name="unit_price" class="form-control" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="stock_qty">Stock Quantity</label>
                <input type="number" id="stock_qty" name="stock_qty" class="form-control" min="0" value="0" required>
            </div>

            <button type="submit" class="btn btn-success">Add Book</button>
        </form>
    </div>
</body>
</html>