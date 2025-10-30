<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Check if the user is already logged in, if so redirect to dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

$message = ""; // To store success/error messages

// Process login/registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = '<div class="message error">Please fill in all fields.</div>';
    } else {
        if ($action === 'register') {
            $result = register_user($conn, $username, $password);
            if ($result['success']) {
                $message = '<div class="message success">' . $result['message'] . '</div>';
            } else {
                $message = '<div class="message error">' . $result['message'] . '</div>';
            }
        } elseif ($action === 'login') {
            $result = login_user($conn, $username, $password);
            if (!$result['success']) {
                $message = '<div class="message error">' . $result['message'] . '</div>';
            }
            // Note: successful login redirects inside login_user()
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookHub - Login / Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="navbar">
            <a href="index.php" class="logo">BookHub</a>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h2 class="text-2xl font-bold mb-4">Access BookHub</h2>
            <?php echo $message; ?>

            <!-- Login Form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="loginForm">
                <input type="hidden" name="action" value="login">
                <h3>Login</h3>
                <div class="form-group">
                    <label for="login_username">Username</label>
                    <input type="text" id="login_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="login_password">Password</label>
                    <input type="password" id="login_password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Log In</button>
            </form>

            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #ccc;">
            
            <!-- Registration Form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="registerForm">
                <input type="hidden" name="action" value="register">
                <h3>New User? Register</h3>
                <div class="form-group">
                    <label for="register_username">Username</label>
                    <input type="text" id="register_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="register_password">Password</label>
                    <input type="password" id="register_password" name="password" required>
                </div>
                <button type="submit" class="btn btn-secondary w-full">Register</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php close_db_connection($conn); ?>
