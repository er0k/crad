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
     * @return BalanceChecker
     */
    public function checkPreviousBalance()
    {
        $this->checker->getTransactions();

        return $this;
    }

    /**
     * @return float
     */
    public function getCurrentBalance()
    {
        return $this->checker->getBalance();
    }

    /**
     * @param  Card   $card
     * @return BalanceChecker\AbstractChecker
     */
    private function getChecker(Card $card)
    {
        $ua = $this->getRandomUserAgentString();

        $number = $card->getNumber();

        switch ($number) {
            case preg_match('|^4[0-9]{12}(?:[0-9]{3})?$|', $number) === 1:
                return new BalanceChecker\VanillaVisa($card, $ua);
                break;
            default:
                throw new BalanceCheckerException("Card type not implemented");
        }
    }

    /**
     * @return string
     * @link https://github.com/rdegges/useragent-api
     */
    private function getRandomUserAgentString()
    {
        $defaultUa = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36';

        if ($response = file_get_contents('http://api.useragent.io')) {
            $data = json_decode($response);
            $ua = $data->ua;
        } else {
            $ua = $defaultUa;
        }

        return $ua;
    }
}