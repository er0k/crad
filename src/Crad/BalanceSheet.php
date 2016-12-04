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

    /**
     * @param \stdClass | null $data
     */
    public function __construct(\stdClass $data = null)
    {
        $this->hydrate($data);
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

        if ($this->hasBalance() && $this->hasTransactions()) {
            return hash('sha512', $this->getBalance() . json_encode($this->getTransactions()));
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
    private function hydrate($data)
    {
        if (is_null($data)) {
            return;
        }

        if (
            isset($data->balance)
            && isset($data->transactions)
        ) {
            $this->setBalance($data->balance);
            $this->setTransactions($data->transactions);

            $this->setHash($this->getHash());
        }
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