<?php

namespace Crad\BalanceChecker;

use Crad\Card;


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
     * @param string    $ua   User Agent String
     */
    public function __construct(Card $card, $ua = '')
    {
        $this->card = $card;
        $this->ua = $ua;
        $this->dom = $this->getDom();
    }

    /**
     * @return float
     */
    abstract public function getBalance();

    /**
     * array['transactions']
     *         ['date']   DateTime
     *         ['desc']   string
     *         ['amount'] float
     *
     * @return array
     */
    abstract public function getTransactions();

    /**
     * @return Symfony\Component\DomCrawler\Crawler
     */
    abstract protected function getDom();

    /**
     * @param  string $amount
     * @return float
     */
    protected function cleanAmount($amount)
    {
        $cleanAmount = preg_replace('|[^0-9-,.]|', '', $amount);

        $cleanAmount = floatval($cleanAmount);

        print_r(compact('cleanAmount'));

        return $cleanAmount;
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
}