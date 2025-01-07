<?php

include_once '../includes/Database.php';
include "../includes/databaseOperations.php";
include "../includes/currencyData.php";

$db = new Database();
$conn = $db->connect();

$result = 0;
$targetCurrency = "";


$currencyData = new CurrencyData();
$currenciesForUrl = $currencyData->currenciesForUrl;
$currencyNamesHu = $currencyData->currencyNamesHu;

if(isset($_POST["submit"])) {
    $baseCurrency = $_POST["from-currency"];
    $targetCurrency = $_POST["to-currency"];
    $amount = $_POST["amount"];

    $result = convertCurrency($conn, $amount, $baseCurrency, $targetCurrency);
}



?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valutaváltó - Kalkulátor</title>
    <link rel="stylesheet" href="../css/style.css">
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
        <li><a href="exchange.php">Váltó</a></li>
    </ul>
</nav>

<main>
    <h1>Kalkulátor</h1>
    <form method="post" action="">
        <label for="amount">Összeg:</label>
        <input type="number" id="amount" name="amount" step="0.01" min="0" required>
        <label for="result">Összeg: </label><?php echo floor($result * 100) / 100 . " " . $targetCurrency; ?>

        <div class="convert">
            <div>
                <!-- Original Currency -->
                <p id="from-currency-fullname"><?= $currencyNamesHu["RON"] ?></p>
                <label for="from-currency">Eredeti valuta:</label>
                <select id="from-currency" name="from-currency"
                        onchange="updateCurrencyFullName('from-currency', 'from-currency-fullname')">
                    <?php foreach ($currenciesForUrl as $currency): ?>
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


<!--        <input type="submit" name="submit" value="Számítás">-->
        <button type="submit" name="submit">Számítás</button>
    </form>
</main>
</body>
</html>
