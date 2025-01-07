<?php
if(isset($_SESSION['loggedin']) && !$_SESSION['loggedin']){
    header('location:dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés / Regisztráció</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function toggleForms() {
            const registrationForm = document.getElementById('registration-form');
            const loginForm = document.getElementById('login-form');

            if (registrationForm.style.display === 'none') {
                registrationForm.style.display = 'block';
                loginForm.style.display = 'none';
            } else {
                registrationForm.style.display = 'none';
                loginForm.style.display = 'block';
            }
        }
    </script>
</head>
<body>
<nav>
    <ul>
        <li><a href="index.php">Kezdőlap</a></li>
        <li><a href="calculator.php">Kalkulátor</a></li>
    </ul>
</nav>

<main>
    <div id="registration-form">
        <h1>Regisztráció</h1>
        <form method="post" action="register.php">
            <label for="new-username">Felhasználónév:</label>
            <input type="text" id="new-username" name="new-username" required>

            <label for="new-password">Jelszó:</label>
            <input type="password" id="new-password" name="new-password" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <button type="submit">Regisztráció</button>
        </form>
        <p>Van már fiókja? <a href="#" onclick="toggleForms()">Jelentkezzen be itt!</a></p>
    </div>

    <div id="login-form" style="display: none;">
        <h1>Bejelentkezés</h1>
        <form method="post" action="authenticate.php">
            <label for="username">Felhasználónév:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Jelszó:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Bejelentkezés</button>
        </form>
        <p>Nincs még fiókja? <a href="#" onclick="toggleForms()">Regisztráljon itt!</a></p>
    </div>
</main>
</body>
</html>