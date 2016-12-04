<?php

namespace Crad\BalanceChecker;

interface BalanceCheckable
{

    public function __construct(Crad\Card $card, $ua = '');


    /**
     * @return float
     */
    public function getBalance();
}