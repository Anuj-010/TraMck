<?php
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $expense_id = $_GET['id'];

    // Perform the delete operation based on $expense_id
    $sql = "DELETE FROM expenses WHERE expense_id = '$expense_id'";
    if ($conn->query($sql) === TRUE) {
        // Expense deleted successfully
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error deleting expense: " . $conn->error;
        exit();
    }
}
?>
