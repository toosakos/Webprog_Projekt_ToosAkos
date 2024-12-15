<?php
include_once "Database.php";
session_start();
if (!isset($_SESSION['loggedin']) && !$_SESSION['loggedin']) {
    header('Location: login-register.php');
}

$targetCurrency = "";
$error = "none";
$bruh = "none";
if (isset($_SESSION["bruh"])){
    $bruh = $_SESSION["bruh"];
    unset($_SESSION["bruh"]);
}

$db = new Database();
$conn = $db->connect();

$id = $_SESSION['id'];

$sql = "SELECT * FROM wallet where users_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$walletCurrencies = [];
$walletAmounts = [];
$status = "";

while ($row = $result->fetch_assoc()) {
    $walletCurrencies[] = $row['currency_code'];
    $walletAmounts[$row['currency_code']] = $row['amount'];
}

$resultConvert = 0;

if (isset($_POST["submit"])) {
    $amount = $_POST["amount"];
    $baseCurrency = $_POST["from-currency"];
    $targetCurrency = $_POST["to-currency"];
    if ($amount > $walletAmounts[$baseCurrency]) {
        $error = "block";
    } else {
        $sql = "SELECT rate FROM currencies WHERE currencybase = '$baseCurrency' AND currencytarget = '$targetCurrency'";
        $rate = $conn->query($sql)->fetch_assoc();
        $rate = $rate["rate"];
        $resultConvert = $rate * $amount;


    $sql = "SELECT EXISTS(SELECT 1 FROM wallet WHERE currency_code = ? and users_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $targetCurrency, $id);
    $stmt->execute();
    $stmt->bind_result($exists);
    $stmt->fetch();
    $stmt->close();

    if ($exists) {
        $sql = "SELECT amount FROM wallet WHERE currency_code = ? and users_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $targetCurrency, $id);
        $stmt->execute();
        $stmt->bind_result($amountWallet);
        $stmt->fetch();
        $stmt->close();

        if ($baseCurrency == $targetCurrency) {
            $_SESSION["bruh"] = "block";
            header('Location: exchange.php');
            exit;
        }

        $oldAmmount = $walletAmounts[$baseCurrency] - $amount;
        $newAmmount = $amountWallet + $resultConvert;

        $sql = "UPDATE wallet SET amount = ? WHERE users_id = ? and currency_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('dis', $oldAmmount, $id, $baseCurrency);
        $stmt->execute();
        $stmt->close();

        $sql = "UPDATE wallet SET amount = ? WHERE users_id = ? and currency_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('dis', $newAmmount, $id, $targetCurrency);
        $stmt->execute();
        $stmt->close();

        $createdAt = date("Y-m-d H:i:s", time());
        $sql = "INSERT INTO transactions (currencybase, currencytarget, amountbase, amounttarget, transaction_date, users_id) values (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssddsi', $baseCurrency, $targetCurrency, $amount, $resultConvert, $createdAt ,$id);
        $stmt->execute();
        $stmt->close();

        $status = "Sikeres Valutaváltás!";

    } else {
        $startingAmount = $resultConvert;
        $baseCurrencyCode = $_POST["to-currency"];
        $createdAt = date("Y-m-d H:i:s", time());

        $oldAmmount = $walletAmounts[$baseCurrency] - $amount;

        $sql = "UPDATE wallet SET amount = ? WHERE users_id = ? and currency_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('dis', $oldAmmount, $id, $baseCurrency);
        $stmt->execute();
        $stmt->close();

        $sql = 'INSERT INTO wallet (amount, currency_code, updated_at, users_id) VALUES (?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('dssi', $startingAmount, $baseCurrencyCode, $createdAt, $id);
        $stmt->execute();
        $stmt->close();

        $oldAmmount = $walletAmounts[$baseCurrency] - $amount;

        $sql = "INSERT INTO transactions (currencybase, currencytarget, amountbase, amounttarget, transaction_date, users_id) values (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssddsi', $baseCurrency, $targetCurrency, $amount, $startingAmount, $createdAt ,$id);
        $stmt->execute();
        $stmt->close();

        $status = "Sikeres Valutaváltás!";
    }
    }

$conn->close();
}

// Currency data
$currenciesForUrl = ["RON", "EUR", "USD", "GBP", "HUF", "PLN", "CHF", "CAD", "AUD", "BGN", "RSD", "MDL", "NOK", "SEK", "DKK", "TRY", "RUB", "JPY", "CNY", "CZK", "ILS"];
$currencyNamesHu = [
    "RON" => "Román lej",
    "EUR" => "Euró",
    "USD" => "Amerikai dollár",
    "GBP" => "Brit font",
    "HUF" => "Magyar forint",
    "PLN" => "Lengyel zloty",
    "CHF" => "Svájci frank",
    "CAD" => "Kanadai dollár",
    "AUD" => "Ausztrál dollár",
    "BGN" => "Bolgár leva",
    "RSD" => "Szerb dinár",
    "MDL" => "Moldován lej",
    "NOK" => "Norvég korona",
    "SEK" => "Svéd korona",
    "DKK" => "Dán korona",
    "TRY" => "Török líra",
    "RUB" => "Orosz rubel",
    "JPY" => "Japán jen",
    "CNY" => "Kínai jüan",
    "CZK" => "Cseh korona",
    "ILS" => "Izraeli sékel"
];


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valutaváltó - Váltó</title>
    <link rel="stylesheet" href="style.css">
    <script>
        // JavaScript object for currency names in Hungarian
        const currencyNamesHu = {
            "RON": "Román lej",
            "EUR": "Euró",
            "USD": "Amerikai dollár",
            "GBP": "Brit font",
            "HUF": "Magyar forint",
            "PLN": "Lengyel zloty",
            "CHF": "Svájci frank",
            "CAD": "Kanadai dollár",
            "AUD": "Ausztrál dollár",
            "BGN": "Bolgár leva",
            "RSD": "Szerb dinár",
            "MDL": "Moldován lej",
            "NOK": "Norvég korona",
            "SEK": "Svéd korona",
            "DKK": "Dán korona",
            "TRY": "Török líra",
            "RUB": "Orosz rubel",
            "JPY": "Japán jen",
            "CNY": "Kínai jüan",
            "CZK": "Cseh korona",
            "ILS": "Izraeli sékel"
        };

        // Function to update the currency full name
        function updateCurrencyFullName(selectId, labelId) {
            const selectElement = document.getElementById(selectId);
            const selectedCurrency = selectElement.value;
            const fullName = currencyNamesHu[selectedCurrency] || "";
            document.getElementById(labelId).textContent = fullName;
        }
    </script>
</head>
<body>
<nav>
    <ul>
        <li><a href="dashboard.php">Kezdőlap</a></li>
        <li><a href="calculator.php">Kalkulátor</a></li>
        <li><a href="exchange.php">Váltó</a></li>
    </ul>
</nav>

<main>
    <h1>Valutaváltás</h1>
    <form method="post">
        <label for="amount">Összeg:</label>
        <input type="number" id="amount" name="amount"  step="0.01" required>
        <label for="result">Összeg: </label><?php echo floor($resultConvert * 100) / 100 . " " . $targetCurrency; ?>
        <p style="display: <?php echo $error ?>">Nincs elég összeg a tárcában!</p>

        <div class="convert">
            <div>
                <!-- Original Currency -->
                <p id="from-currency-fullname"><?= $currencyNamesHu["RON"] ?></p>
                <label for="from-currency">Eredeti valuta:</label>
                <select id="from-currency" name="from-currency"
                        onchange="updateCurrencyFullName('from-currency', 'from-currency-fullname')">
                    <?php foreach ($walletCurrencies as $currency): ?>
                        <option value="<?= $currency ?>"><?= $currency ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <!-- Target Currency -->
                <p id="to-currency-fullname"><?= $currencyNamesHu["RON"] ?></p>
                <label for="to-currency">Cél valuta:</label>
                <select id="to-currency" name="to-currency"
                        onchange="updateCurrencyFullName('to-currency', 'to-currency-fullname')">
                    <?php foreach ($currenciesForUrl as $currency): ?>
                        <option value="<?= $currency ?>"><?= $currency ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" name="submit">Számítás</button>
        <p style="display: <?php echo $bruh ?>">Nem lehet egy valutát saját magára váltani!</p>
    </form>
    <a href="dashboard.php"><?php echo $status?></a>
</main>
</body>
</html>

