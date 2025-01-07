<?php

function convertCurrency($conn, $amount, $fromCurrency, $toCurrency) : float
{
    $sql = "SELECT rate FROM currencies WHERE currencybase = '$fromCurrency' AND currencytarget = '$toCurrency'";
    $rate = $conn->query($sql)->fetch_assoc();
    $rate = $rate["rate"];
    return $rate * $amount;
}

function isExistingWalletCurrency($conn, $targetCurrency, $id): bool
{
    $exists = false;
    $sql = "SELECT EXISTS(SELECT 1 FROM wallet WHERE currency_code = ? and users_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $targetCurrency, $id);
    $stmt->execute();
    $stmt->bind_result($exists);
    $stmt->fetch();
    $stmt->close();

    if ($exists) {
        return true;
    } else {
        return false;
    }
}

function currentAmount($conn, $targetCurrency, $id): float
{
    $amountWallet = 0;
    $sql = "SELECT amount FROM wallet WHERE currency_code = ? and users_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $targetCurrency, $id);
    $stmt->execute();
    $stmt->bind_result($amountWallet);
    $stmt->fetch();
    $stmt->close();
    return $amountWallet;
}

function convertIntoNewCurrency($conn, $baseCurrency, $oldAmmount, $targetCurrency, $startingAmount, $id, $createdAt)
{

    $sql = "UPDATE wallet SET amount = ? WHERE users_id = ? and currency_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('dis', $oldAmmount, $id, $baseCurrency);
    $stmt->execute();
    $stmt->close();

    $sql = 'INSERT INTO wallet (amount, currency_code, updated_at, users_id) VALUES (?, ?, ?, ?)';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('dssi', $startingAmount, $targetCurrency, $createdAt, $id);
    $stmt->execute();
    $stmt->close();


    $sql = "INSERT INTO transactions (currencybase, currencytarget, amountbase, amounttarget, transaction_date, users_id) values (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssddsi', $baseCurrency, $targetCurrency, $oldAmmount, $startingAmount, $createdAt ,$id);
    $stmt->execute();
    $stmt->close();

}

function convertIntoExistingCurrency($conn, $baseCurrency, $oldAmmount, $targetCurrency, $startingAmount, $id, $createdAt): void
{
    $sql = "UPDATE wallet SET amount = ? WHERE users_id = ? and currency_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('dis', $oldAmmount, $id, $baseCurrency);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE wallet SET amount = ? WHERE users_id = ? and currency_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('dis', $startingAmount, $id, $targetCurrency);
    $stmt->execute();
    $stmt->close();


    $sql = "INSERT INTO transactions (currencybase, currencytarget, amountbase, amounttarget, transaction_date, users_id) values (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssddsi', $baseCurrency, $targetCurrency, $oldAmmount, $startingAmount, $createdAt ,$id);
    $stmt->execute();
    $stmt->close();

}


function fetchWalletCurrencies($conn, $userId): array
{
    $sql = "SELECT * FROM wallet WHERE users_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $walletCurrencies = [];
    $walletAmounts = [];

    while ($row = $result->fetch_assoc()) {
        $walletCurrencies[] = $row['currency_code'];
        $walletAmounts[$row['currency_code']] = $row['amount'];
    }

    return ['currencies' => $walletCurrencies, 'amounts' => $walletAmounts];
}

function fetchTransactions($conn, $userId): array
{
    $sql = "SELECT * FROM transactions WHERE users_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }

    return $transactions;
}

function addToWallet($conn, $userId, $currencyCode, $amount, $createdAt): void
{
    $sql = 'INSERT INTO wallet (amount, currency_code, updated_at, users_id) VALUES (?, ?, ?, ?)';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('dssi', $amount, $currencyCode, $createdAt, $userId);
    $stmt->execute();
    $stmt->close();
}

function updateWalletAmount($conn, $userId, $currencyCode, $newAmount): void
{
    $sql = "UPDATE wallet SET amount = ? WHERE users_id = ? AND currency_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('dis', $newAmount, $userId, $currencyCode);
    $stmt->execute();
    $stmt->close();
}

function recordTransaction($conn, $userId, $baseCurrency, $targetCurrency, $amountBase, $amountTarget, $createdAt): void
{
    $sql = "INSERT INTO transactions (currencybase, currencytarget, amountbase, amounttarget, transaction_date, users_id) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssddsi', $baseCurrency, $targetCurrency, $amountBase, $amountTarget, $createdAt, $userId);
    $stmt->execute();
    $stmt->close();
}

function walletCurrencyExists($conn, $userId, $currencyCode): bool
{
    $exists = false;
    $sql = "SELECT EXISTS(SELECT 1 FROM wallet WHERE currency_code = ? AND users_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $currencyCode, $userId);
    $stmt->execute();
    $stmt->bind_result($exists);
    $stmt->fetch();
    $stmt->close();

    return $exists;
}

function getWalletAmount($conn, $userId, $currencyCode): float
{
    $amountWallet = 0;
    $sql = "SELECT amount FROM wallet WHERE currency_code = ? AND users_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $currencyCode, $userId);
    $stmt->execute();
    $stmt->bind_result($amountWallet);
    $stmt->fetch();
    $stmt->close();

    return $amountWallet;
}

