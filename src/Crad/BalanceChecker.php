<?php

namespace Crad;

class BalanceChecker
{

    private $card;

    public function __construct(Card $card, Storage $storage)
    {
        $this->card = $card;
        $this->storage = $storage;
    }

    public function checkPreviousBalance()
    {
        return $this;
    }

    public function getCurrentBalance()
    {
        return 0;
    }
}