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
        $balanceSheet = $this->checker->getBalanceSheet();

        return $balanceSheet;
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
}