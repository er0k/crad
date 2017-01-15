<?php

namespace Crad\BalanceChecker;

use Crad\Card;
use Crad\BalanceSheet;
use Crad\BalanceCheckerException;

abstract class AbstractChecker
{
    /** @var Card */
    protected $card;

    /** @var string User Agent String */
    protected $ua;

    /** @var Symfony\Component\DomCrawler\Crawler */
    protected $dom;


    /**
     * @param Crad\Card $card
     */
    public function __construct(Card $card)
    {
        $this->card = $card;
        $this->ua = $this->getRandomUserAgentString();
        $this->dom = $this->getDom();
    }

    /**
     * @return Symfony\Component\DomCrawler\Crawler
     */
    abstract protected function getDom();

    /**
     * @return float
     */
    abstract protected function getBalance();

    /**
     * array['transactions']
     *         ['date']   DateTime
     *         ['desc']   string
     *         ['amount'] float
     *
     * @return array
     */
    abstract protected function getTransactions();

    /**
     * @return BalanceSheet
     */
    public function makeBalanceSheet()
    {
        $balanceSheet = new BalanceSheet();

        $balanceSheet->setBalance($this->getBalance());
        $balanceSheet->setTransactions($this->getTransactions());
        $balanceSheet->setHash($this->card->getHash());

        return $balanceSheet;
    }

    /**
     * @param  string $amount
     * @return float
     */
    protected function cleanAmount($amount)
    {
        return floatval(preg_replace('|[^0-9-,.]|', '', $amount));
    }

    /**
     * @param  string $desc
     * @return string
     */
    protected function cleanDescription($desc)
    {
        return trim($desc);
    }

    /**
     * @param  string $date
     * @return \DateTime
     */
    protected function cleanDate($date)
    {
        return new \DateTime($date);
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