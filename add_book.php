<?php
require_once 'auth.php';
require_once 'db_connect.php';

check_auth(); 

$user_id = $_SESSION['id'];
$message = '';

// Process book creation form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $pages = intval($_POST['pages'] ?? 0);
    $status = $_POST['status'] ?? 'To Read';
    $progress = intval($_POST['progress'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if (empty($title) || empty($author)) {
        $message = '<div class="message error">Title and Author are required fields.</div>';
    } else {
        // Simple validation for pages/progress (Server-side defense)
        if ($pages < 0 || $progress < 0 || $progress > $pages) {
            $message = '<div class="message error">Invalid page or progress values. Progress cannot exceed total pages.</div>';
        } else {
            // Recalculate progress for DB storage (as percentage of pages read)
            $progress_percent = $pages > 0 ? round(($progress / $pages) * 100) : 0;
            if ($status === 'Completed') {
                $progress_percent = 100;
            }

            // Prepare an INSERT statement (Create)
            $sql = "INSERT INTO books (user_id, title, author, genre, pages, status, progress, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("isssisis", $user_id, $title, $author, $genre, $pages, $status, $progress_percent, $notes);
                
                if ($stmt->execute()) {
                    $message = '<div class="message success">Book successfully added to your library!</div>';
                    // Clear post data to show a fresh form after success
                    $_POST = array(); 
                } else {
                    $message = '<div class="message error">Error adding book: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="message error">Database error: Could not prepare statement.</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookHub - Add New Book</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="navbar">
            <a href="dashboard.php" class="logo">BookHub</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card" style="max-width: 600px;">
            <h2>Add New Book to Track</h2>
            <?php echo $message; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="bookForm">
                <div class="form-group">
                    <label for="title">Title <span style="color: red;">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="author">Author <span style="color: red;">*</span></label>
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="genre">Genre</label>
                    <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($_POST['genre'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="pages">Total Pages (0 if unknown)</label>
                    <input type="number" id="pages" name="pages" min="0" value="<?php echo htmlspecialchars($_POST['pages'] ?? 0); ?>">
                </div>
                
                <div class="form-group">
                    <label for="progress">Pages Read (Current Progress)</label>
                    <input type="number" id="progress" name="progress" min="0" value="<?php echo htmlspecialchars($_POST['progress'] ?? 0); ?>">
                    <small>Cannot be greater than Total Pages. Used to calculate percentage.</small>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="To Read" <?php echo (($_POST['status'] ?? 'To Read') == 'To Read') ? 'selected' : ''; ?>>To Read</option>
                        <option value="Reading" <?php echo (($_POST['status'] ?? '') == 'Reading') ? 'selected' : ''; ?>>Reading</option>
                        <option value="Completed" <?php echo (($_POST['status'] ?? '') == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes/Review</label>
                    <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Save Book</button>
                <a href="dashboard.php" class="btn btn-secondary" style="width: 100%; margin-top: 10px; text-align: center;">Cancel</a>
            </form>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
<?php close_db_connection($conn); ?>
