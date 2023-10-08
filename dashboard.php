<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include('db.php'); // Include your database connection


function getInitialBudget($userId, $conn) {
    $sql = "SELECT initial_budget FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "Error preparing SQL statement: " . $conn->error; // Print the error message
        return 0;
    }
    $stmt->bind_param('i', $userId); // 'i' indicates an integer parameter

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            return $row ? $row['initial_budget'] : 0;
        } else {
            echo "Error fetching result: " . $conn->error; // Print the error message
            return 0;
        }
    } else {
        echo "Error executing SQL statement: " . $conn->error; // Print the error message
        return 0;
    }
}

function getUserBudget($userId, $conn) {
    $sql = "SELECT COALESCE(SUM(amount), 0) AS current_budget FROM expenses WHERE user_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('i', $userId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                return $row ? $row['current_budget'] : 0;
            } else {
                echo "Error fetching result: " . $conn->error;
            }
        } else {
            echo "Error executing SQL statement: " . $conn->error;
        }
    } else {
        echo "Error preparing SQL statement: " . $conn->error;
    }

    return 0; // Default value in case of an error
}

function getUserCategoryBudget($userId, $category ,$conn) {
    $sql = "SELECT COALESCE(SUM(amount), 0) AS current_budget FROM expenses WHERE user_id = ? AND category = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('is', $userId, $category);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                return $row ? $row['current_budget'] : 0;
            } else {
                echo "Error fetching result: " . $conn->error;
            }
        } else {
            echo "Error executing SQL statement: " . $conn->error;
        }
    } else {
        echo "Error preparing SQL statement: " . $conn->error;
    }

    return 0; // Default value in case of an error
}

// Function to get the user's category budget
function getCategoryBudget($userId, $category, $conn) {
    $sql = "SELECT budget_amount FROM category_budgets WHERE user_id = ? AND category = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "Error preparing SQL statement: " . $conn->error; // Print the error message
        return 0;
    }
    $stmt->bind_param('is', $userId, $category);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            return $row ? $row['budget_amount'] : 0;
        } else {
            echo "Error fetching result: " . $conn->error; // Print the error message
            return 0;
        }
    } else {
        echo "Error executing SQL statement: " . $conn->error; // Print the error message
        return 0;
    }
}

// Function to update the user's category budget
// Function to update the user's category budget
function updateCategoryBudget($userId, $category, $category_budget, $conn) {
    // Check if the category exists
    $sqlCheckCategory = "SELECT * FROM category_budgets WHERE user_id = ? AND category = ?";
    $stmtCheckCategory = $conn->prepare($sqlCheckCategory);
    $stmtCheckCategory->bind_param('is', $userId, $category);
    $stmtCheckCategory->execute();
    $resultCheckCategory = $stmtCheckCategory->get_result();

    if ($resultCheckCategory->num_rows > 0) {
        // Category exists, update the budget
        $sqlUpdate = "UPDATE category_budgets SET budget_amount = ? WHERE user_id = ? AND category = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if (!$stmtUpdate) {
            echo "Error preparing SQL statement: " . $conn->error; // Print the error message
            return false;
        }
        $stmtUpdate->bind_param('iss', $category_budget, $userId, $category);
        if ($stmtUpdate->execute()) {
            return true;
        } else {
            echo "Error executing SQL statement: " . $stmtUpdate->error; // Print the error message
            return false;
        }
    } else {
        // Category doesn't exist, insert a new record
        $sqlInsert = "INSERT INTO category_budgets (user_id, category, budget_amount) VALUES (?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        if (!$stmtInsert) {
            echo "Error preparing SQL statement: " . $conn->error; // Print the error message
            return false;
        }
        $stmtInsert->bind_param('iss', $userId, $category, $category_budget);
        if ($stmtInsert->execute()) {
            return true;
        } else {
            echo "Error executing SQL statement: " . $stmtInsert->error; // Print the error message
            return false;
        }
    }
}



$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['set_budget'])) {
        // Setting a new budget
        $budget = $_POST['budget'];

        // Update the budget in the users table
        $query = "UPDATE users SET initial_budget = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo "Error preparing SQL statement: " . $conn->error;
        } else {
            $stmt->bind_param('di', $budget, $user_id);
            if ($stmt->execute()) {
                echo "Budget set successfully!";
            } else {
                echo "Error executing SQL statement: " . $stmt->error;
            }
        }
    } 
    elseif(isset($_POST['set_category_budget'])){

        $category = $_POST['category'];
        $category_budget = $_POST['category_budget'];

        updateCategoryBudget($user_id, $category, $category_budget, $conn);
    }
    
    elseif (isset($_POST['add_expense'])) {
        // Adding an expense
        $amount = $_POST['amount'];
        $category = $_POST['category'];
        $description = $_POST['description']; 

        // Insert the expense into the expenses table
        $upload_date = date("Y-m-d H:i:s");
        $upload_time = date("H:i:s");
        $sql = "INSERT INTO expenses (user_id, amount, category, description, upload_date, upload_time) VALUES ('$user_id', '$amount', '$category', '$description', '$upload_date', '$upload_time')";
        $result = $conn->query($sql);
        if ($result === TRUE) {
            echo "Expense added successfully!";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    elseif(isset($_POST['delete_category'])) {
        $budget_id_to_delete = $_POST['budget_id_to_delete'];

        // Assuming you have a column 'budget_id' in category_budgets table
        $sqlDeleteCategory = "DELETE FROM category_budgets WHERE budget_id = ?";
        
        $stmtDeleteCategory = $conn->prepare($sqlDeleteCategory);
    
        if ($stmtDeleteCategory) {
            $stmtDeleteCategory->bind_param('i', $budget_id_to_delete);
            
            if ($stmtDeleteCategory->execute()) {
                echo "Category deleted successfully!";
            } else {
                echo "Error executing SQL statement: " . $stmtDeleteCategory->error;
            }
            
            $stmtDeleteCategory->close();
        } else {
            echo "Error preparing SQL statement: " . $conn->error;
        }
    
    }
}


// $user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM expenses WHERE user_id = '$user_id'";
$result_expenses = $conn->query($sql);

// Retrieve the user's budget

$userInitialBudget = getInitialBudget($user_id, $conn);

$userTotalExpenses = getUserBudget($user_id, $conn);

$userBudget = $userInitialBudget - $userTotalExpenses;

$sqlFetchCategories = "SELECT budget_id, category FROM category_budgets";
$resultFetchCategories = $conn->query($sqlFetchCategories);


if (date('j') == 1) {
    // Set the initial budget for the user
    setInitialBudget($user_id, $conn);
}
?>




<!DOCTYPE html>
<html>
<head>
    <title>Expense Tracker</title>
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
    
    <h3>Set Budget</h3>
    <form method="post" action="dashboard.php">
        Budget Amount: <input type="text" name="budget" value="<?php echo $userInitialBudget; ?>" required><br>
        <input type="submit" name="set_budget" value="Set Budget">
    </form>

    <h3>Add Expense</h3>
    <form method="post" action="dashboard.php">
        Amount: <input type="text" name="amount" required><br>
        <!-- Add other expense fields here -->
        
        <label for="category">Select Category:</label>
    <select name="category">
        <!-- Fetch and display existing categories from your database -->
        <?php
        $sqlCategoryOptions = "SELECT DISTINCT category FROM category_budgets WHERE user_id = ?";
        $stmtCategoryOptions = $conn->prepare($sqlCategoryOptions);
        $stmtCategoryOptions->bind_param('i', $user_id);
        $stmtCategoryOptions->execute();
        $resultCategoryOptions = $stmtCategoryOptions->get_result();

        while ($rowCategoryOption = $resultCategoryOptions->fetch_assoc()) {
            echo "<option value='{$rowCategoryOption['category']}'>{$rowCategoryOption['category']}</option>";
        }
        ?>
    </select><br>
        Description: <input type="text" name="description" required><br>
        <input type="submit" name="add_expense" value="Add Expense">
    </form>

    <!-- Display the remaining budget here -->
    <h1>Your Budget Dashboard</h1>

<!-- Display Total Budget -->
<p> Budget Remaining: <?php echo $userBudget; ?></p>

<!-- Form to set Category Budget -->
<form method="post" action="dashboard.php">
    

    <label for="category_budget">Set Budget for Category:</label><br>
    Category: <input type="text" name="category" required><br>
    <input type="text" name="category_budget" required>

    <input type="submit" name="set_category_budget" value="Set Category Budget">
</form>

<!-- Display Category Budgets -->
<?php
$sqlCategories = "SELECT * FROM category_budgets WHERE user_id = ?";
$stmtCategories = $conn->prepare($sqlCategories);
$stmtCategories->bind_param('i', $user_id);
$stmtCategories->execute();
$resultCategories = $stmtCategories->get_result();

while ($rowCategory = $resultCategories->fetch_assoc()) {
    $category = $rowCategory['category'];
    $categoryBudget = getCategoryBudget($user_id, $category, $conn);

    $expense = getUserCategoryBudget($user_id, $category, $conn);

    $newCategoryBudget = $categoryBudget - $expense;
    echo "<p>Budget for $category: $newCategoryBudget</p>";

    // echo "<a href='deleteCat.php?id={$rowCategory['budget_id']}'>Delete</a>";
    
}

// while($rowCat = $resultcats->fetch_assoc()){
//     echo "<td><a href='update.php?id={$row['expense_id']}'>Update</a>";
// }
?>

<h2>Delete Category</h2>
    
    <form method="post" action="delete_category.php">
        <label for="budget_id_to_delete">Select Category to Delete:</label>
        <select name="budget_id_to_delete">
            <?php
            while ($rowCategory = $resultFetchCategories->fetch_assoc()) {
                echo "<option value='{$rowCategory['budget_id']}'>{$rowCategory['category']}</option>";
            }
            ?>
        </select>
        
        <input type="submit" name="delete_category" value="Delete Category">
    </form>

<br>


    <h3>Your Expenses</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Amount</th>
                <th>Category</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = $result_expenses->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . date("Y-m-d", strtotime($row['upload_date'])) . "</td>";
                echo "<td>" . $row['upload_time'] . "</td>";
                echo "<td>" . $row['amount'] . "</td>";
                echo "<td>" . $row['category'] . "</td>";
                echo "<td>" . $row['description'] . "</td>";
                echo "<td><a href='update.php?id={$row['expense_id']}'>Update</a> | <a href='delete.php?id={$row['expense_id']}'>Delete</a></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
