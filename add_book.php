<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"], textarea, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h1>Add New Book</h1>

    <?php
    include 'ConnDB.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = $_POST['title'];
        $isbn = $_POST['isbn'];
        $category = $_POST['category'];
        $page_number = $_POST['page_number'];
        $unit_price = $_POST['unit_price'];

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO books (title, isbn, category, page_number, unit_price) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdd", $title, $isbn, $category, $page_number, $unit_price);

        if ($stmt->execute()) {
            echo "<div class='success'>New book added successfully!</div>";
        } else {
            echo "<div class='error'>Error: " . $stmt->error . "</div>";
        }

        $stmt->close();
    }
    ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label for="title">Book Title:</label>
            <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="isbn">ISBN:</label>
            <input type="text" id="isbn" name="isbn" required>
        </div>

        <div class="form-group">
            <label for="category">Category:</label>
            <input type="text" id="category" name="category" required>
        </div>

        <div class="form-group">
            <label for="page_number">Page Number:</label>
            <input type="number" id="page_number" name="page_number" min="1" required>
        </div>

        <div class="form-group">
            <label for="unit_price">Unit Price:</label>
            <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" required>
        </div>

        <button type="submit">Add Book</button>
    </form>
</body>
</html>