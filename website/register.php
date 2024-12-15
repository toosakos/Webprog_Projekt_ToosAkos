<?php
include_once 'Database.php';

session_start();
$db = new Database();
$conn = $db->connect();

// Now we check if the data from the login form was submitted, isset() will check if the data exists.
if (!isset($_POST['new-username'], $_POST['new-password'], $_POST['email'])) {
    // Could not get the data that should have been sent.
    exit('Please fill both the username and password fields!');
}

// Assuming $conn is your MySQLi connection
$newUsername = $_POST["new-username"];
$sql = 'SELECT EXISTS(SELECT 1 FROM users WHERE name = ?)';
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $newUsername);
$stmt->execute();
$stmt->bind_result($exists);
$stmt->fetch();
$stmt->close();

if ($exists) {
    exit('Username already exists!');
}

//wallet base attributes:
$startingAmount = 0;
$baseCurrencyCode = "RON";
$createdAt = date("Y-m-d H:i:s", time());

//$sql = 'INSERT INTO wallet (amount, currency_code, updated_at) VALUES (?, ?, ?)';
//$stmt = $conn->prepare($sql);
//$stmt->bind_param('dss', $startingAmount, $baseCurrencyCode, $createdAt );
//$stmt->execute();
//$stmt->close();
//
//$sql = 'SELECT id FROM wallet ORDER BY id DESC LIMIT 1';
//$newWalletId = $conn->query($sql)->fetch_assoc()['id'];
//
//$sql = 'INSERT INTO users (name, email, password, wallet_id) VALUES (?, ?, ?, ?)';
//$stmt = $conn->prepare($sql);
//$stmt->bind_param('sssi', $newUsername, $email, $password, $newWalletId );
//$stmt->execute();
//$stmt->close();
//
//$sql = 'SELECT id FROM users ORDER BY id DESC LIMIT 1';
//$newUserId = $conn->query($sql)->fetch_assoc()['id'];

$email = $_POST['email'];
$password = password_hash($_POST["new-password"], PASSWORD_DEFAULT);

$sql = 'INSERT INTO users (name, email, password) VALUES (?, ?, ?)';
$stmt = $conn->prepare($sql);
$stmt->bind_param('sss', $newUsername, $email, $password);
$stmt->execute();
$stmt->close();

$sql = 'SELECT id FROM users ORDER BY id DESC LIMIT 1';
$newUserId = $conn->query($sql)->fetch_assoc()['id'];

$_SESSION['loggedin'] = TRUE;
$_SESSION['username'] = $newUsername;
$_SESSION['id'] = $newUserId;
header('Location: dashboard.php');