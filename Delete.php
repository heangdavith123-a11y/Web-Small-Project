<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Book</title>
</head>
<body>
    <?php
    include 'ConnDB.php';

    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];

        // Delete book (soft delete)
        $stmt = $conn->prepare("UPDATE books SET is_deleted = 1 WHERE book_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: inventory.php");
            exit();
        } else {
            echo "Error deleting book: " . $stmt->error;
        }
    } elseif (isset($_GET['sale_id']) && is_numeric($_GET['sale_id'])) {
        $sale_id = $_GET['sale_id'];

        // Delete sale and its details
        $conn->begin_transaction();
        try {
            // Delete sale details first
            $stmt = $conn->prepare("DELETE FROM salse_details WHERE sale_id = ?");
            $stmt->bind_param("i", $sale_id);
            $stmt->execute();
            $stmt->close();

            // Delete sale
            $stmt = $conn->prepare("DELETE FROM sales WHERE sale_id = ?");
            $stmt->bind_param("i", $sale_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            header("Location: sales_history.php"); // Redirect to sales history after deleting a sale
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo "Error deleting sale: " . $e->getMessage();
        }
    } else {
        echo "Invalid ID.";
    }

    $conn->close();
    ?>
</body>
</html>
