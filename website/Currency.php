<?php

class Currency
{
    private string $currencyCode;
    private array $currencyRates;

    public function __construct($currencyCode)
    {
        $this->currencyCode = $currencyCode;
    }

    public function addCurrencyRate($currencyCode, $rate): void
    {
        $this->currencyRates[$currencyCode] = $rate;
    }

    public function getCurrencyRate($currencyCode)
    {
        return $this->currencyRates[$currencyCode];
    }

    public function getCurrencyRates(): array
    {
        return $this->currencyRates;
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

}