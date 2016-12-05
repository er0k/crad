<?php

namespace Crad;

class BalanceSheet implements \JsonSerializable, EncryptedStorable
{
    /** @var float */
    private $balance;

    /** @var array */
    private $transactions;

    /** @var string */
    private $hash;

    const SHOW_OUTPUT = true;


    /**
     * @return BalanceSheet
     */
    public function showInfo()
    {
        if (self::SHOW_OUTPUT) {
            print_r([
                'balance' => $this->getBalance(),
                'transactions' => $this->getTransactions(),
                #'hash' => $this->getHash(),
            ]);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasAllData()
    {
        if (!$this->hasBalance()) {
            return false;
        }

        if (!$this->hasTransactions()) {
            return false;
        }

        if (!$this->hasHash()) {
            return false;
        }

        return true;
    }

    public function hasChanged(BalanceSheet $balanceSheet)
    {
        if (!$balanceSheet->hasAllData() || !$this->hasAllData()) {
            throw new BalanceSheetException("Cannot compare balance sheets without all data");
        }

        if (json_encode($balanceSheet) === json_encode($this)) {
            return false;
        }

        return true;
    }

    /**
     * @param float $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @return bool
     */
    public function hasBalance()
    {
        return !is_null($this->balance);
    }

    /**
     * @param array $transactions
     */
    public function setTransactions(array $transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * @return array
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @return bool
     */
    public function hasTransactions()
    {
        return !is_null($this->transactions);
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        if ($this->hasHash()) {
            return $this->hash;
        }
    }

    /**
     * @return bool
     */
    public function hasHash()
    {
        return !is_null($this->hash);
    }

    /**
     * @param  stdClass $data
     * @return void
     */
    public function hydrate($data)
    {
        if (is_null($data)) {
            return;
        }

        if (
            isset($data->balance)
            && isset($data->transactions)
            && isset($data->hash)
        ) {
            $this->setBalance($data->balance);
            $this->setTransactions($data->transactions);
            $this->setHash($data->hash);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'balance' => $this->getBalance(),
            'transactions' => $this->getTransactions(),
            'hash' => $this->getHash(),
        ];
    }
}