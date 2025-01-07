<?php
include_once "Database.php";
include "databaseOperations.php";
include "currencyData.php";

session_start();
if (!isset($_SESSION['loggedin']) && !$_SESSION['loggedin']) {
    header('Location: login_register.php');
    exit;
}

$error = "";

$db = new Database();
$conn = $db->connect();
$userId = $_SESSION['id'];

$currencyData = new CurrencyData();
$currenciesForUrl = $currencyData->currenciesForUrl;
$currencyNamesHu = $currencyData->currencyNamesHu;

$walletData = fetchWalletCurrencies($conn, $userId);
$walletCurrencies = $walletData['currencies'];
$walletAmounts = $walletData['amounts'];

$transactions = fetchTransactions($conn, $userId);

// Wallet Deposit
if (isset($_POST['submit'])) {
    $startingAmount = $_POST["amount"];
    $baseCurrencyCode = $_POST["from-currency"];
    $createdAt = date("Y-m-d H:i:s", time());

    if (walletCurrencyExists($conn, $userId, $baseCurrencyCode)) {
        $currentAmount = getWalletAmount($conn, $userId, $baseCurrencyCode) + $startingAmount;
        updateWalletAmount($conn, $userId, $baseCurrencyCode, $currentAmount);
    } else {
        addToWallet($conn, $userId, $baseCurrencyCode, $startingAmount, $createdAt);
    }

    recordTransaction($conn, $userId, $baseCurrencyCode, $baseCurrencyCode, 0, $startingAmount, $createdAt);
    header('Location: dashboard.php');
    exit;
}

// Wallet Withdrawal
if (isset($_POST['submit2'])) {
    $startingAmount = $_POST["amountoff"];
    $baseCurrencyCode = $_POST["to-currency"];
    $createdAt = date("Y-m-d H:i:s", time());

    if (walletCurrencyExists($conn, $userId, $baseCurrencyCode)) {
        $currentAmount = getWalletAmount($conn, $userId, $baseCurrencyCode);
        if ($currentAmount >= $startingAmount) {
            $newAmount = $currentAmount - $startingAmount;
            updateWalletAmount($conn, $userId, $baseCurrencyCode, $newAmount);
            recordTransaction($conn, $userId, $baseCurrencyCode, $baseCurrencyCode, $startingAmount, $newAmount, $createdAt);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Nincs elég egyenleg!";
        }
    } else {
        $error = "Valuta nem létezik!";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <ul>
        <li><a href="calculator.php">Kalkulátor</a></li>
        <li><a href="exchange.php">Váltó</a></li>
        <li style="margin-left: auto;"><a href="logout.php">Kijelentkezés</a></li>
    </ul>
</nav>

<main>
    <h1>Üdvözlünk <?php echo $_SESSION["username"]/* . " id: " .  $_SESSION["id"]*/ ?>!</h1>
    <div class="options">
        <a href="calculator.php" class="option-button">Kalkulátor</a>
        <a href="exchange.php" class="option-button">Valutaváltó</a>
    </div>
    <h2>Pénztárcába feltöltés:</h2>
    <form method="POST"
          style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 10px; background-color: #f9f9f9;">
        <div style="display: flex; flex-direction: column; gap: 15px; align-items: center;">
            <!-- Currency Selection -->
            <div style="display: flex; flex-direction: column; gap: 5px; width: 100%;">
                <label for="from-currency" style="font-weight: bold;">Válaszd ki a valutát:</label>
                <select id="from-currency" name="from-currency"
                        onchange="updateCurrencyFullName('from-currency', 'from-currency-fullname')"
                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
                    <?php foreach ($currenciesForUrl as $currency): ?>
                        <option value="<?= $currency ?>"><?= $currency ?></option>
                    <?php endforeach; ?>
                </select>
                <p id="from-currency-fullname"
                   style="font-style: italic; margin-top: 5px;"><?= $currencyNamesHu["RON"] ?></p>
            </div>

            <!-- Amount Input -->
            <div style="display: flex; flex-direction: column; gap: 5px; width: 100%;">
                <label for="amount" style="font-weight: bold;">Add meg az összeget:</label>
                <input type="number" id="amount" name="amount"
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;"
                       placeholder="Összeg" step="0.01" min="0" required>
            </div>

            <!-- Submit Button -->
            <input type="submit" name="submit" value="Feltöltés"
                   style="padding: 10px 20px; background-color: #333; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">

        </div>
    </form>

    <!-- Wallet Table -->
    <div style="margin-top: 30px; max-width: 600px; margin: auto;">
        <h3 style="text-align: center;">Jelenlegi pénztárca:</h3>
        <table style="width: 100%; border: 1px solid #ccc; border-collapse: collapse; text-align: left;">
            <thead>
            <tr style="background-color: #333; color: white;">
                <th style="padding: 10px; border: 1px solid #ccc;">Valutakód</th>
                <th style="padding: 10px; border: 1px solid #ccc;">Összeg</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($walletAmounts as $code => $amount): ?>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ccc;"><?= $code ?></td>
                    <td style="padding: 10px; border: 1px solid #ccc;"><?= number_format($amount, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Transactions Table -->
    <div style="margin-top: 30px; max-width: 600px; margin: auto;">
        <h3 style="text-align: center;">Tranzakciók:</h3>
        <table style="width: 100%; border: 1px solid #ccc; border-collapse: collapse; text-align: left;">
            <thead>
            <tr style="background-color: #333; color: white;">
                <th style="padding: 10px; border: 1px solid #ccc;">Alap valuta</th>
                <th style="padding: 10px; border: 1px solid #ccc;">Alap összeg</th>
                <th style="padding: 10px; border: 1px solid #ccc;">Cél valuta</th>
                <th style="padding: 10px; border: 1px solid #ccc;">Cél összeg</th>
                <th style="padding: 10px; border: 1px solid #ccc;">Dátum</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ccc;"><?= $transaction["currencybase"] ?></td>
                    <td style="padding: 10px; border: 1px solid #ccc;"><?= number_format($transaction["amountbase"], 2) ?></td>
                    <td style="padding: 10px; border: 1px solid #ccc;"><?= $transaction["currencytarget"] ?></td>
                    <td style="padding: 10px; border: 1px solid #ccc;"><?= number_format($transaction["amounttarget"], 2) ?></td>
                    <td style="padding: 10px; border: 1px solid #ccc;"><?= $transaction["transaction_date"] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <h2>Egyenleg levevése:</h2>
    <form method="POST"
          style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 10px; background-color: #f9f9f9;">
        <div style="display: flex; flex-direction: column; gap: 15px; align-items: center;">
            <!-- Currency Selection -->
            <div style="display: flex; flex-direction: column; gap: 5px; width: 100%;">
                <label for="to-currency" style="font-weight: bold;">Válaszd ki a valutát:</label>
                <select id="to-currency" name="to-currency"
                        onchange="updateCurrencyFullName('to-currency', 'to-currency-fullname')"
                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
                    <?php foreach ($walletCurrencies as $currency): ?>
                        <option value="<?= $currency ?>"><?= $currency ?></option>
                    <?php endforeach; ?>
                </select>
                <p id="from-currency-fullname"
                   style="font-style: italic; margin-top: 5px;"><?= $currencyNamesHu["RON"] ?></p>
            </div>

            <!-- Amount Input -->
            <div style="display: flex; flex-direction: column; gap: 5px; width: 100%;">
                <label for="amountoff" style="font-weight: bold;">Add meg az összeget:</label>
                <input type="number" id="amountoff" name="amountoff"
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;"
                       placeholder="Összeg" step="0.01" min="0" required>
            </div>

            <!-- Submit Button -->
            <input type="submit" name="submit2" value="Levétel"
                   style="padding: 10px 20px; background-color: #333; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
            <div><?php echo $error ?></div>
        </div>
    </form>
</main>
</body>
</html>
