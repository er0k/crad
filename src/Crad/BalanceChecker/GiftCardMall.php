<?php

namespace Crad\BalanceChecker;

use Crad\Card;
use Crad\BalanceCheckerException;
use Goutte\Client;

class GiftCardMall extends AbstractChecker
{
    const URL = 'https://mygift.giftcardmall.com/Card/Login?returnURL=Transactions';

    private $client;

    /**
     * @return Symfony\Component\DomCrawler\Crawler
     * @throws BalanceCheckerException
     */
    protected function getDom()
    {
        if ($this->dom) {
            return $this->dom;
        }

        $this->client = new Client();

        if (!empty($this->ua)) {
            $this->client->setHeader('user-agent', $this->ua);
        }

        $dom = $this->client->request('GET', self::URL);

        $form = $dom->selectButton('Next')->form();
        $dom = $this->client->submit($form, [
            'CardNumber' => $this->card->getNumber(),
            'ExpirationMonth' => $this->card->getMonth(),
            'ExpirationYear' => $this->card->getYear(),
            'SecurityCode' => $this->card->getCvv(),
        ]);

        if (!$dom->filter('.bd-transaction-area')->count()) {
            throw new BalanceCheckerException("Wrong credentials");
        }

        return $dom;
    }

    /**
     * @return float
     * @throws BalanceCheckerException
     */
    protected function getBalance()
    {
        if (!$this->dom) {
            throw new BalanceCheckerException("No DOM");
        }

        // #main > section.primaryLeft.box > div > table > tbody > tr > td:nth-child(3) > h5
        $domBalance = $this->dom->filter('table.bd-wizard-account-header td:nth-child(3) > h5')->text();

        return $this->cleanAmount($domBalance);
    }

    /**
     * @return array
     * @throws BalanceCheckerException
     */
    protected function getTransactions()
    {
        if (!$this->dom) {
            throw new BalanceCheckerException("No DOM");
        }

        $today = new \DateTime();

        $form = $this->dom->selectButton('Search')->form();
        $this->dom = $this->client->submit($form, [
            'DateFrom' => '1/1/2001',
            'DateTo' => $today->format('n/j/Y'),
        ]);

        $transactions = [];

        $this->dom->filter('#TransactionsGrid table')->filter('tr.t-master-row')->each(function ($node, $i) use (&$transactions) {
            echo $i . "\n";
            // #TransactionsGrid > table > tbody > tr:nth-child(1) > td:nth-child(2)
            $domDate = $node->filter('td:nth-child(2)')->text();
            $domDesc = $node->filter('td:nth-child(4)')->text();
            $domAmount = $node->filter('td:nth-child(5)')->text();

            $transactions[$i]['date'] = $this->cleanDate($domDate);
            $transactions[$i]['desc'] = $this->cleanDescription($domDesc);
            $transactions[$i]['amount'] = $this->cleanAmount($domAmount);
        });


        return $transactions;
    }
}