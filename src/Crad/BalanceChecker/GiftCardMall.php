<?php

namespace Crad\BalanceChecker;

use Crad\Card;
use Crad\BalanceCheckerException;
use Goutte\Client;

class GiftCardMall extends AbstractChecker
{
    const URL = 'https://mygift.giftcardmall.com/Card/Login?returnURL=Transactions';

    /**
     * @return Symfony\Component\DomCrawler\Crawler
     * @throws BalanceCheckerException
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

        $form = $dom->selectButton('Next')->form();
        $dom = $client->submit($form, [
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

        print_r(compact('domBalance'));

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

        return [];

        $transactions = [];

        $this->dom->filter('.txnStripe')->each(function ($node, $i) use (&$transactions) {
            $domDate = $node->filter('.txnDate')->text();
            $domDesc = $node->filter('.txnDesc')->text();

            $transactions[$i]['date'] = $this->cleanDate($domDate);
            $transactions[$i]['desc'] = $this->cleanDescription($domDesc);

            // sometimes there are multiple amounts for some reason
            // get the one that's not empty
            $node->filter('.txnAmount')->each(function ($innerNode) use ($i, &$transactions) {
                $domAmount = trim($innerNode->text());
                if (!empty($domAmount)) {
                    $transactions[$i]['amount'] = $this->cleanAmount($domAmount);
                    return;
                }
            });
        });

        return $transactions;
    }
}