<?php

namespace Crad\BalanceChecker;

use Crad\Card;
use Goutte\Client;

class VanillaVisa extends AbstractChecker
{
    const URL = 'https://www.onevanilla.com';

    /**
     * @return float
     */
    public function getBalance()
    {
        $domBalance = $this->dom->filter('#Avlbal')->text();

        return $this->cleanAmount($domBalance);
    }

    /**
     * @return array
     */
    public function getTransactions()
    {
        $transactions = [];

        $this->dom->filter('.txnStripe')->each(function ($node, $i) use (&$transactions) {
            $domDate = $node->filter('.txnDate')->text();
            $domDesc = $node->filter('.txnDesc')->text();

            $transactions[$i]['date'] = $this->cleanDate($domDate);
            $transactions[$i]['desc'] = $this->cleanDescription($domDesc);

            // sometimes there are multiple amounts for some reason
            // get the one that's not empty
            $node->filter('.txnAmount')->each (function ($innerNode) use ($i, &$transactions) {
                $domAmount = trim($innerNode->text());
                if (!empty($domAmount)) {
                    $transactions[$i]['amount'] = $this->cleanAmount($domAmount);
                    break;
                }
            });
        });

        // sort by date
        usort($transactions, function($a, $b) {
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] < $b['date']) ? -1 : 1;
        }

        print_r(compact('transactions'));

        return $transactions;
    }

    /**
     * @return Symfony\Component\DomCrawler\Crawler
     */
    protected function getDom()
    {
        if ($this->dom) {
            return $this->dom;
        }

        $client = new Client();

        if (!empty($this->ua)) {
            $client->setHeader('user-agent', $this->ua);
        }

        $dom = $client->request('GET', self::URL);
        $form = $dom->selectButton('Sign In')->form();
        $dom = $client->submit($form, [
            'cardNumber' => $this->card->getNumber(),
            'expMonth' => $this->card->getMonth(),
            'expYear' => $this->card->getYear(),
            'cvv' => $this->card->getCvv(),
        ]);

        return $dom;
    }

}