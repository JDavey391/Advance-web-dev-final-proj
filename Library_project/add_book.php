<?php
// Initialize the session ONCE at the very top.
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'db_connect.php';
$message = ''; 

// This block will only run when the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $category_id = $_POST['category_id'];

    if (!empty($title) && !empty($author) && !empty($category_id)) {
        
        // DUPLICATE CHECK
        $sql_check = "SELECT book_id FROM books WHERE isbn = ? OR (title = ? AND author = ?)";
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("sss", $isbn, $title, $author);
            $stmt_check->execute();
            $stmt_check->store_result();
            
            if ($stmt_check->num_rows > 0) {
                $message = '<div class="alert alert-danger" role="alert">Error: A book with this ISBN or Title/Author combination already exists.</div>';
            } else {
                // No duplicate found, proceed with inserting the new book
                $sql_insert = "INSERT INTO books (title, author, isbn, category_id_fk) VALUES (?, ?, ?, ?)";
                if ($stmt_insert = $conn->prepare($sql_insert)) {
                    $stmt_insert->bind_param("sssi", $title, $author, $isbn, $category_id);
                    if ($stmt_insert->execute()) {
                        $message = '<div class="alert alert-success" role="alert">New book added successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger" role="alert">Error: Could not execute the query.</div>';
                    }
                    $stmt_insert->close();
                }
            }
            $stmt_check->close();
        }
    } else {
        $message = '<div class="alert alert-warning" role="alert">Please fill in title, author, and select a category.</div>';
    }
    // NOTICE: We DO NOT close the connection here anymore.
}

// This query now runs AFTER the POST logic, ensuring the connection is still open.
$categories_result = $conn->query("SELECT category_id, name FROM categories ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 600px;">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Add a New Book</h2>
                
                <?php if(!empty($message)) echo $message; ?>

                <form action="add_book.php" method="post">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="author" class="form-label">Author</label>
                        <input type="text" class="form-control" id="author" name="author" required>
                    </div>
                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control" id="isbn" name="isbn">
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">Select a category</option>
                            <?php 
                            if ($categories_result) {
                                while($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary">Back to Home</a>
                        <button type="submit" class="btn btn-primary">Add Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
// This is now the ONLY place the connection is closed.
$conn->close(); 
?>