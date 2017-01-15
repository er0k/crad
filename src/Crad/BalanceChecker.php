<?php

namespace Crad;

class BalanceChecker
{
    /** @var BalanceChecker\AbstractChecker */
    private $checker;

    /**
     * @param Card $card
     */
    public function __construct(Card $card)
    {
        $this->checker = $this->getChecker($card);
    }

    /**
     * @return BalanceSheet
     */
    public function getBalanceSheet()
    {
        return $this->checker->makeBalanceSheet();
    }

    /**
     * @param  Card   $card
     * @return BalanceChecker\AbstractChecker
     */
    private function getChecker(Card $card)
    {
        $number = $card->getNumber();

        switch ($number) {
            case preg_match('|^43[0-9]{11}(?:[0-9]{3})?$|', $number) === 1:
                return new BalanceChecker\GiftCardMall($card);
            case preg_match('|^4[0-9]{12}(?:[0-9]{3})?$|', $number) === 1:
                return new BalanceChecker\VanillaVisa($card);
            default:
                throw new BalanceCheckerException("Card type not implemented");
        }
    }

    /**
     * @param  float $balance
     * @param  array $transactions
     */
    public function compareBalanceToTransactionTotal(BalanceSheet $balanceSheet)
    {
        $balance = $balanceSheet->getBalance();
        $transactions = $balanceSheet->getTransactions();

        // sort transactions by date (oldest to newest)
        usort($transactions, function($a, $b) {
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] < $b['date']) ? -1 : 1;
        });

        $transactionTotal = 0;
        foreach ($transactions as $transaction) {
            $transactionTotal += $transaction['amount'];
        }

        if (!$this->isEqual($transactionTotal, $balance)) {
            echo "Transaction total ($transactionTotal) does not match current balance ($balance)\n";
        }
    }

    /**
     * @param  string $a
     * @param  string $b
     * @return bool
     */
    private function isEqual($a, $b)
    {
        if (bccomp("$a", "$b", 3) === 0) {
            return true;
        }

        return false;
    }
}