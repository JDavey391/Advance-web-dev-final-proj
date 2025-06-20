<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// --- The rest of your original delete_book.php code ---
if(isset($_GET['id']) && !empty(trim($_GET['id']))){
    
    require_once 'db_connect.php';
    
    $sql = "DELETE FROM books WHERE book_id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $param_id);
        
        $param_id = trim($_GET['id']);
        
        if($stmt->execute()){
            header("location: index.php");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
     
    $stmt->close();
    
    $conn->close();

} else{
    header("location: index.php");
    exit();
}
?>