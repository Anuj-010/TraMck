<?php
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['id'])) {
    $budget_id = $_GET['id'];

    // Perform the delete operation based on $expense_id
    $sql = "DELETE FROM category_budgets WHERE budget_id = '$budget_id'";
    if ($conn->query($sql) === TRUE) {
        // Expense deleted successfully
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error deleting Category: " . $conn->error;
        exit();
    }
}
?>