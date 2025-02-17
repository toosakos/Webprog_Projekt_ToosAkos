<?php
include_once '../includes/Database.php';

session_start();
$db = new Database();
$con = $db->connect();

// Now we check if the data from the login form was submitted, isset() will check if the data exists.
if (!isset($_POST['username'], $_POST['password'])) {
    // Could not get the data that should have been sent.
    exit('Please fill both the username and password fields!');
}

// Prepare our SQL, preparing the SQL statement will prevent SQL injection.
if ($stmt = $con->prepare('SELECT id, password FROM users WHERE name = ?')) {
    // Bind parameters (s = string, i = int, b = blob, etc), in our case the username is a string so we use "s"
    $stmt->bind_param('s', $_POST['username']);
    $stmt->execute();
    // Store the result so we can check if the account exists in the database.
    $stmt->store_result();
    //$stmt->close();
}

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $password);
    $stmt->fetch();
    // Account exists, now we verify the password.
    // Note: remember to use password_hash in your registration file to store the hashed passwords.
    if (password_verify($_POST['password'], $password)) {
        // Verification success! User has logged-in!
        // Create sessions, so we know the user is logged in, they basically act like cookies but remember the data on the server.
        //session_regenerate_id();
        $_SESSION['loggedin'] = TRUE;
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['id'] = $id;
        //echo 'Welcome ' . $_SESSION['name'] . '!';
        header('Location: dashboard.php');
    } else {
        // Incorrect password
        echo 'Incorrect username and/or !password!';
    }
} else {
    // Incorrect username
    echo 'Incorrect !username and/or password!';
}
$stmt->close();
