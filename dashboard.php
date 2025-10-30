<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Ensure the user is logged in
check_auth(); 

$user_id = $_SESSION['id'];
$username = $_SESSION['username'];
$books = [];
$message = '';

// Handle 'Mark as Completed' action (Update part of CRUD)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_completed']) && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);
    
    // Prepare an UPDATE statement
    $sql = "UPDATE books SET status = 'Completed', progress = 100 WHERE id = ? AND user_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $book_id, $user_id);
        if ($stmt->execute()) {
            $message = '<div class="message success">Book marked as Completed! Great job!</div>';
        } else {
            $message = '<div class="message error">Error marking book as completed.</div>';
        }
        $stmt->close();
    }
}

// Fetch all books for the current user (Read part of CRUD)
$sql = "SELECT id, title, author, genre, status, pages, progress, notes FROM books WHERE user_id = ? ORDER BY title ASC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    } else {
        $message = '<div class="message error">Could not retrieve books. Database error.</div>';
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookHub - Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="navbar">
            <a href="dashboard.php" class="logo">BookHub</a>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                <a href="add_book.php">Add New Book</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-header">
            <h2>Your E-Library & Reading Tracker</h2>
            <a href="add_book.php" class="btn btn-primary">Add New Book</a>
        </div>
        
        <?php echo $message; ?>

        <?php if (empty($books)): ?>
            <div class="message secondary">
                You haven't added any books yet. Click "Add New Book" to start tracking your reading journey!
            </div>
        <?php else: ?>
            <div class="book-list">
                <?php foreach ($books as $book): 
                    // Calculate progress percentage
                    $progress_percent = $book['pages'] > 0 ? round(($book['progress'] / $book['pages']) * 100) : 0;
                    if ($book['status'] === 'Completed') {
                        $progress_percent = 100;
                    }
                ?>
                    <div class="book-card">
                        <div>
                            <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p>by <strong><?php echo htmlspecialchars($book['author']); ?></strong></p>
                            <p>Genre: <?php echo htmlspecialchars($book['genre']); ?></p>
                            <p class="status status-<?php echo str_replace(' ', '-', $book['status']); ?>"><?php echo htmlspecialchars($book['status']); ?></p>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
                            </div>
                            <p style="font-size: 0.9rem; margin-top: 5px;">Progress: <?php echo $progress_percent; ?>%</p>
                            
                            <?php if (!empty($book['notes'])): ?>
                                <p style="font-style: italic; margin-top: 10px;">Notes: <?php echo substr(htmlspecialchars($book['notes']), 0, 50) . '...'; ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="card-actions">
                            <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-secondary" style="flex-grow: 1;">Edit / View</a>
                            
                            <?php if ($book['status'] !== 'Completed'): ?>
                                <form action="dashboard.php" method="post" style="margin: 0;">
                                    <input type="hidden" name="mark_completed" value="1">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" class="btn btn-primary" style="flex-grow: 1; background-color: #f6ad55;">Mark Read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php close_db_connection($conn); ?>
