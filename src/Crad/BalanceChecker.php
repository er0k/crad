<?php

namespace Crad;

class BalanceChecker
{
    /** @var BalanceChecker\BalanceCheckable */
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
     * @return BalanceChecker\BalanceCheckable
     */
    private function getChecker(Card $card)
    {
        $checker = null;

        $ua = $this->getRandomUserAgentString();

        $number = $card->getNumber();

        switch ($number) {
            case preg_match('|^4[0-9]{12}(?:[0-9]{3})?$|', $number) === 1:
                $checker = new BalanceChecker\VanillaVisa($card, $ua);
                break;
            default:
                throw new BalanceCheckerException("Card type no implemented");
        }

        return $checker;

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