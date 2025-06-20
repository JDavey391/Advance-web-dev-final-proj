<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'db_connect.php';
$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    if (!empty($category_name)) {
        $sql = "INSERT INTO categories (name) VALUES (?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $category_name);
            if ($stmt->execute()) {
                $message = "Category added successfully!";
            } else {
                $error = "Error: This category might already exist.";
            }
            $stmt->close();
        }
    } else {
        $error = "Category name cannot be empty.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    $sql = "DELETE FROM categories WHERE category_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $category_id);
        if ($stmt->execute()) {
            $message = "Category deleted successfully!";
        } else {
            $error = "Error deleting category.";
        }
        $stmt->close();
    }
}

$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5 mb-5">
    <header class="app-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="my-0">Manage Categories</h4>
            <a href="index.php" class="btn btn-outline-light">Back to Dashboard</a>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="alert alert-success mt-4"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger mt-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row mt-4">
        <div class="col-md-5">
             <div class="card">
                <div class="card-header">Add a New Category</div>
                <div class="card-body">
                    <form action="manage_categories.php" method="post" class="d-flex">
                        <input type="text" name="category_name" class="form-control me-2" placeholder="e.g., Fantasy">
                        <button type="submit" name="add_category" class="btn btn-primary">Add</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">Existing Categories</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center" style="background: none; color: inherit; border-color: var(--bs-table-border-color);">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <form action="manage_categories.php" method="post" onsubmit="return confirm('Are you sure? Deleting a category will uncategorize books in it.');">
                                    <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                    <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>