<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'db_connect.php';

$categories_result = $conn->query("SELECT category_id, name FROM categories ORDER BY name ASC");
$search_term = '';
$search_category = '';

$sql = "SELECT b.book_id, b.title, b.author, b.isbn, b.is_available, c.name AS category_name 
        FROM books AS b 
        LEFT JOIN categories AS c ON b.category_id_fk = c.category_id";

$where_conditions = [];
$params = [];
$param_types = '';

if (isset($_GET['search_query']) && !empty(trim($_GET['search_query']))) {
    $search_term = trim($_GET['search_query']);
    $where_conditions[] = "(b.title LIKE ? OR b.author LIKE ?)";
    $params[] = "%" . $search_term . "%";
    $params[] = "%" . $search_term . "%";
    $param_types .= 'ss';
}

if (isset($_GET['search_category']) && !empty($_GET['search_category'])) {
    $search_category = $_GET['search_category'];
    $where_conditions[] = "b.category_id_fk = ?";
    $params[] = $search_category;
    $param_types .= 'i';
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " ORDER BY b.title ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5 mb-5">
        <header class="app-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="my-0">Welcome, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>!</h4>
                <div>
                    <a href="manage_categories.php" class="btn btn-outline-light">Manage Categories</a>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </header>
        
        <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
            <h2>Library Book List</h2>
            <a href="add_book.php" class="btn btn-primary">Add New Book</a>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <form action="index.php" method="get" class="row g-3 align-items-center">
                    <div class="col-md-5">
                         <input type="text" name="search_query" class="form-control" placeholder="Search by title or author..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="search_category" class="form-select">
                            <option value="">All Categories</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0); // Reset pointer
                            while($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php if ($category['category_id'] == $search_category) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-info w-100">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-dark table-bordered table-striped table-hover">
             <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['author']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['isbn']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['category_name'] ?? 'N/A') . "</td>";
                        if ($row['is_available'] == 1) {
                            echo '<td><span class="badge bg-success">Available</span></td>';
                        } else {
                            echo '<td><span class="badge bg-warning">Checked Out</span></td>';
                        }
                        echo '<td>';
                        echo '<a href="update_book.php?id=' . $row['book_id'] . '" class="btn btn-sm btn-light me-1">Edit</a>';
                        echo '<a href="delete_book.php?id=' . $row['book_id'] . '" class="btn btn-sm btn-outline-danger">Delete</a>';
                        echo '</td>';
                        echo "</tr>";
                    }
                } else {
                    echo '<tr><td colspan="6" class="text-center">No books found matching your criteria.</td></tr>';
                }
                $stmt->close();
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>