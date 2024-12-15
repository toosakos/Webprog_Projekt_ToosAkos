<?php

include_once "Currency.php";
include_once "Database.php";

$db = new Database();
$conn = $db->connect();

$currenciesForUrl = ["RON","EUR","USD","GBP","HUF","PLN","CHF","CAD","AUD","BGN","RSD","MDL","NOK","SEK","DKK","TRY","RUB","JPY","CNY","CZK","ILS"];
$strCurrenciesForUrl = implode(",", $currenciesForUrl);

// Prepare CSV file for writing
$csvFile = fopen("exchange_rates.csv", "w");
if (!$csvFile) {
    die("Failed to create CSV file.");
}

// Write CSV headers
fputcsv($csvFile, ["Base Currency", "Target Currency", "Rate"]);

$allCurrenicies = [];

for ($i = 0; $i < count($currenciesForUrl); $i++) {
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.fxratesapi.com/latest?base=" . $currenciesForUrl[$i] . "&currencies="
            . $strCurrenciesForUrl . "&amount=1&places=6&format=json",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("cURL Error for base {$currenciesForUrl[$i]}: " . $err);
        continue; // Skip to the next iteration
    }

    $currencies = json_decode($response, true);

    if (!isset($currencies["base"]) || !isset($currencies["rates"])) {
        error_log("Invalid response for base {$currenciesForUrl[$i]}: " . print_r($currencies, true));
        continue; // Skip to the next iteration
    }

    $baseCurrency = new Currency($currencies["base"]);

    foreach ($currencies["rates"] as $targetCurrency => $rate) {
        $baseCurrency->addCurrencyRate($targetCurrency, $rate);

        // Write to CSV file
        fputcsv($csvFile, [$currencies["base"], $targetCurrency, $rate]);
    }

    $allCurrenicies[] = $baseCurrency;
    echo "Processed base currency: " . $baseCurrency->getCurrencyCode() . "\n";
}

// Close the CSV file
fclose($csvFile);

// Prepare SQL statement for inserting data into the database
$sql = "
    INSERT INTO currencies (currencybase, currencytarget, rate)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE rate = VALUES(rate)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Statement preparation failed: " . $conn->error);
    exit;
}

// Bind parameters and execute for each exchange rate
foreach ($allCurrenicies as $currency) {
    $currencyBase = $currency->getCurrencyCode();
    foreach ($currency->getCurrencyRates() as $targetCurrency => $rate) {
        $stmt->bind_param("ssd", $currencyBase, $targetCurrency, $rate);

        // Log the data being inserted
        error_log("Preparing to insert: Base = {$currency->getCurrencyCode()}, Target = $targetCurrency, Rate = $rate");

        if (!$stmt->execute()) {
            error_log("Failed to execute statement: " . $stmt->error);
        }
    }
}

// Close the statement and connection
$stmt->close();
$conn->close();

echo "Exchange rates successfully uploaded to the database and saved in exchange_rates.csv.";
