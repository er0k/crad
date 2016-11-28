<?php

namespace Crad;

class BalanceChecker
{
    private $card;

    public function setCard(Card $card)
    {
        $this->card = $card;
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