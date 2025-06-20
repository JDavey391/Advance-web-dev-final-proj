<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'db_connect.php';

$title = $author = $isbn = "";
$message = '';
$current_category_id = null;
$book_id = $_GET['id'] ?? null; // Use null coalescing for safety

// Fetch all categories for the dropdown, this needs to be available always
$categories_result = $conn->query("SELECT category_id, name FROM categories ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $book_id = $_POST["id"];
    $title = trim($_POST["title"]);
    $author = trim($_POST["author"]);
    $isbn = trim($_POST["isbn"]);
    $current_category_id = $_POST['category_id'];

    if (!empty($title) && !empty($author) && !empty($current_category_id)) {
        // DUPLICATE CHECK for UPDATE
        $sql_check = "SELECT book_id FROM books WHERE (isbn = ? OR (title = ? AND author = ?)) AND book_id != ?";
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("sssi", $isbn, $title, $author, $book_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $message = '<div class="alert alert-danger" role="alert">Error: Another book with this ISBN or Title/Author combination already exists.</div>';
            } else {
                // No duplicate, proceed with the update
                $sql_update = "UPDATE books SET title=?, author=?, isbn=?, category_id_fk=? WHERE book_id=?";
                if ($stmt_update = $conn->prepare($sql_update)) {
                    $stmt_update->bind_param("sssii", $title, $author, $isbn, $current_category_id, $book_id);
                    if ($stmt_update->execute()) {
                        header("location: index.php");
                        exit();
                    }
                }
            }
            $stmt_check->close();
        }
    }
} else {
    // This part is to pre-fill the form when the page is first loaded
    if (!empty($book_id)) {
        $sql = "SELECT title, author, isbn, category_id_fk FROM books WHERE book_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $book_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows == 1) {
                    $row = $result->fetch_assoc();
                    $title = $row["title"];
                    $author = $row["author"];
                    $isbn = $row["isbn"];
                    $current_category_id = $row["category_id_fk"];
                } else {
                    header("location: index.php");
                    exit();
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 600px;">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Edit Book Details</h2>
                
                <?php if(!empty($message)) echo $message; ?>

                <form action="update_book.php?id=<?php echo $book_id; ?>" method="post">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="author" class="form-label">Author</label>
                        <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($author); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" name="isbn" class="form-control" value="<?php echo htmlspecialchars($isbn); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">Select a category</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0); 
                            while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php if ($category['category_id'] == $current_category_id) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <input type="hidden" name="id" value="<?php echo $book_id; ?>"/>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>