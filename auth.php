<?php
// Start the session to manage user state across pages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';

// Function to safely check if a user is authenticated
function check_auth() {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        // Not logged in, redirect to login page
        header("location: index.php");
        exit;
    }
}

// Function to handle user registration
function register_user($conn, $username, $password) {
    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare an INSERT statement
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("ss", $param_username, $param_password);
        $param_username = $username;
        $param_password = $hashed_password;

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            return ["success" => true, "message" => "Registration successful. You can now log in."];
        } else {
            // Check for duplicate username error
            if ($conn->errno == 1062) {
                 return ["success" => false, "message" => "Username already exists. Please choose another."];
            }
            return ["success" => false, "message" => "Something went wrong during registration. Please try again later."];
        }

        // Close statement
        $stmt->close();
    } else {
        return ["success" => false, "message" => "Database error: Could not prepare statement."];
    }
}

// Function to handle user login
function login_user($conn, $username, $password) {
    // Prepare a SELECT statement
    $sql = "SELECT id, username, password FROM users WHERE username = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("s", $param_username);
        $param_username = $username;

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Store result
            $stmt->store_result();

            // Check if username exists
            if ($stmt->num_rows == 1) {
                // Bind result variables
                $stmt->bind_result($id, $username, $hashed_password);
                if ($stmt->fetch()) {
                    // Verify password
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_regenerate_id();
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $username;
                        
                        // Redirect user to dashboard
                        header("location: dashboard.php");
                        exit;
                    } else {
                        return ["success" => false, "message" => "Invalid username or password."];
                    }
                }
            } else {
                return ["success" => false, "message" => "Invalid username or password."];
            }
        } else {
            return ["success" => false, "message" => "Oops! Something went wrong. Please try again later."];
        }
        $stmt->close();
    }
}

// Function to handle user logout
function logout_user() {
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to login page
    header("location: index.php");
    exit;
}
?>