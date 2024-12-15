<?php

include_once "Currency.php";
include_once "Database.php";

$db = new Database();
$conn = $db->connect();

$currenciesForUrl = ["RON","EUR","USD","GBP","HUF","PLN","CHF","CAD","AUD","BGN","RSD","MDL","NOK","SEK","DKK","TRY","RUB","JPY","CNY","CZK","ILS"];

// Prepare CSV file for writing
$csvFile = fopen("exchange_rates.csv", "w");
if (!$csvFile) {
    die("Failed to create CSV file.");
}

// Write CSV headers
fputcsv($csvFile, ["Base Currency", "Target Currency", "Rate"]);

$allCurrenicies = [];

foreach ($currenciesForUrl as $baseCurrency) {
    $dummyRates = [];
    $baseCurrencyObj = new Currency($baseCurrency);

    foreach ($currenciesForUrl as $targetCurrency) {
        if ($baseCurrency !== $targetCurrency) {
            $rate = round(mt_rand(5000, 150000) / 100000, 6); // Generate a random rate between 0.050000 and 1.500000
            $dummyRates[$targetCurrency] = $rate;

            // Write to CSV file
            fputcsv($csvFile, [$baseCurrency, $targetCurrency, $rate]);

            $baseCurrencyObj->addCurrencyRate($targetCurrency, $rate);
        }
    }

    $allCurrenicies[] = $baseCurrencyObj;
    echo "Processed base currency: $baseCurrency\n";
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

echo "Dummy exchange rates successfully uploaded to the database and saved in exchange_rates.csv.";
