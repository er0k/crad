<?php

namespace Crad\BalanceChecker;

interface BalanceCheckable
{
    /**
     * @return float
     */
    public function getBalance();
}