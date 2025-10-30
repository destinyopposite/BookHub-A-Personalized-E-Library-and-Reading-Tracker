<?php
require_once 'auth.php';
require_once 'db_connect.php';

check_auth(); 

$user_id = $_SESSION['id'];
$book = null;
$message = '';
$book_id = intval($_GET['id'] ?? 0);

if ($book_id === 0) {
    header("location: dashboard.php");
    exit;
}

// --- Fetch Book Data (R of CRUD) ---
$sql_fetch = "SELECT id, title, author, genre, status, pages, progress, notes FROM books WHERE id = ? AND user_id = ?";
if ($stmt_fetch = $conn->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("ii", $book_id, $user_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    if ($result_fetch->num_rows == 1) {
        $book = $result_fetch->fetch_assoc();
        // Since DB stores progress as % (0-100), we need to reverse-calculate pages read for the form
        $book['pages_read'] = $book['pages'] > 0 ? round(($book['progress'] / 100) * $book['pages']) : 0;
    } else {
        $message = '<div class="message error">Book not found or you do not have permission to view it.</div>';
    }
    $stmt_fetch->close();
}


// --- Process Update and Delete (U and D of CRUD) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $book) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $pages = intval($_POST['pages'] ?? 0);
        $status = $_POST['status'] ?? 'To Read';
        $progress_pages = intval($_POST['progress_pages'] ?? 0); // Pages read from form
        $notes = trim($_POST['notes'] ?? '');

        if (empty($title) || empty($author)) {
            $message = '<div class="message error">Title and Author are required fields.</div>';
        } elseif ($pages < 0 || $progress_pages < 0 || $progress_pages > $pages) {
            $message = '<div class="message error">Invalid page or progress values. Progress cannot exceed total pages.</div>';
        } else {
            // Recalculate progress for DB storage (as percentage 0-100)
            $progress_percent = $pages > 0 ? round(($progress_pages / $pages) * 100) : 0;
            if ($status === 'Completed') {
                $progress_percent = 100;
            }

            // Prepare an UPDATE statement
            $sql_update = "UPDATE books SET title=?, author=?, genre=?, pages=?, status=?, progress=?, notes=? WHERE id=? AND user_id=?";
            if ($stmt_update = $conn->prepare($sql_update)) {
                $stmt_update->bind_param("sssisisii", $title, $author, $genre, $pages, $status, $progress_percent, $notes, $book_id, $user_id);
                
                if ($stmt_update->execute()) {
                    $message = '<div class="message success">Book details successfully updated!</div>';
                    // Re-fetch updated data to refresh the form
                    $book['title'] = $title;
                    $book['author'] = $author;
                    $book['genre'] = $genre;
                    $book['pages'] = $pages;
                    $book['status'] = $status;
                    $book['progress'] = $progress_percent;
                    $book['pages_read'] = $progress_pages;
                    $book['notes'] = $notes;
                } else {
                    $message = '<div class="message error">Error updating book: ' . $stmt_update->error . '</div>';
                }
                $stmt_update->close();
            }
        }
    } elseif ($action === 'delete') {
        // Prepare a DELETE statement
        $sql_delete = "DELETE FROM books WHERE id = ? AND user_id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param("ii", $book_id, $user_id);
            if ($stmt_delete->execute()) {
                // Successful delete, redirect to dashboard
                header("location: dashboard.php?msg=deleted");
                exit;
            } else {
                $message = '<div class="message error">Error deleting book.</div>';
            }
            $stmt_delete->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookHub - Edit Book</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="navbar">
            <a href="dashboard.php" class="logo">BookHub</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="add_book.php">Add New Book</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card" style="max-width: 600px;">
            <?php if ($book): ?>
                <h2>Edit Book: <?php echo htmlspecialchars($book['title']); ?></h2>
                <?php echo $message; ?>

                <form action="edit_book.php?id=<?php echo $book['id']; ?>" method="post" id="bookForm">
                    <input type="hidden" name="action" value="update">
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($book['genre']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="pages">Total Pages</label>
                        <input type="number" id="pages" name="pages" min="0" value="<?php echo htmlspecialchars($book['pages']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="progress_pages">Pages Read</label>
                        <input type="number" id="progress_pages" name="progress_pages" min="0" value="<?php echo htmlspecialchars($book['pages_read']); ?>">
                        <small>Current progress percentage: <?php echo $book['progress']; ?>%</small>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="To Read" <?php echo ($book['status'] == 'To Read') ? 'selected' : ''; ?>>To Read</option>
                            <option value="Reading" <?php echo ($book['status'] == 'Reading') ? 'selected' : ''; ?>>Reading</option>
                            <option value="Completed" <?php echo ($book['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes/Review</label>
                        <textarea id="notes" name="notes" rows="6"><?php echo htmlspecialchars($book['notes']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Book Details</button>
                </form>

                <hr style="margin: 25px 0; border: 0; border-top: 1px solid #ccc;">

                <!-- Delete Form: Note the use of onclick to call the JS function -->
                <form id="deleteForm" action="edit_book.php?id=<?php echo $book['id']; ?>" method="post" onsubmit="event.preventDefault(); confirmDelete();">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger" style="width: 100%;">Delete Book</button>
                </form>

            <?php else: ?>
                <div class="message error">Book data is unavailable. Please return to the <a href="dashboard.php">Dashboard</a>.</div>
            <?php endif; ?>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
<?php close_db_connection($conn); ?>
