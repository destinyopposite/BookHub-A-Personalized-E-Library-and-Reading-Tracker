<?php
// --- 1. Database Configuration Constants ---
// These define the four crucial pieces of information needed to log into your MySQL server.

// 1.1 DB_SERVER: The location of the database. For local XAMPP/MAMP, this is always 'localhost'.
define('DB_SERVER', 'localhost');

// 1.2 DB_USER: The MySQL username. (COMMON ERROR POINT!)
// XAMPP default is 'root'. MAMP default is usually 'root'.
define('DB_USER', 'root'); 

// 1.3 DB_PASS: The MySQL password. (COMMON ERROR POINT!)
// XAMPP default is typically an empty string (''). MAMP default is usually 'root'.
define('DB_PASS', '');     

// 1.4 DB_NAME: The name of the database we created.
define('DB_NAME', 'bookhub_db'); 


// --- 2. Attempt Connection ---

// This line attempts to connect to the MySQL database using the four constants defined above.
// The result of the connection attempt is stored in the global variable $conn.
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);


// --- 3. Check for Connection Errors ---

// This checks if the connection attempt failed. If $conn has an error property set,
// it means the database is unreachable (wrong username, password, or DB_NAME).
if ($conn->connect_error) {
    // If there's an error, it stops the script and displays the error message.
    die("Connection failed: " . $conn->connect_error);
}


// --- 4. Optional Function for Closing Connection ---

// This function is defined here so other files can cleanly close the connection when done.
function close_db_connection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
