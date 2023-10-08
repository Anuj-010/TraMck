<?php
session_start();

include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $expense_id = $_GET['id'];
    
    // Fetch the expense data from the database based on $expense_id
    $sql = "SELECT * FROM expenses WHERE expense_id = '$expense_id'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
    } else {
        echo "Expense not found!";
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process the form submission to update the expense
    $expense_id = $_POST['expense_id'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $description = $_POST['description'];

    // Update the expense in the database
    $sql = "UPDATE expenses SET amount = '$amount', category = '$category', description = '$description' WHERE expense_id = '$expense_id'";
    
    if ($conn->query($sql) === TRUE) {
        // Expense updated successfully
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error updating expense: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Expense Tracker</title>
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
    <h3>Update Expense</h3>
    <form method="post" action="">
        <input type="hidden" name="expense_id" value="<?php echo $row['expense_id']; ?>">
        Amount: <input type="text" name="amount" value="<?php echo $row['amount']; ?>" required><br>
        Category: <input type="text" name="category" value="<?php echo $row['category']; ?>" required><br>
        Description: <input type="text" name="description" value="<?php echo $row['description']; ?>" required><br>
        <input type="submit" value="Update Expense">
    </form>
</body>
</html>



