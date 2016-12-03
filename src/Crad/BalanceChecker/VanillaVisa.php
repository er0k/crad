<?php

namespace Crad\BalanceChecker;

use Crad\Card;
use Goutte\Client;

class VanillaVisa implements BalanceCheckable
{
    const URL = 'https://www.onevanilla.com';

    /** @var Card */
    private $card;

    /** @var string User Agent String */
    private $ua;

    /**
     * @param Card   $card
     * @param string $ua
     */
    public function __construct(Card $card, $ua = '')
    {
        $this->card = $card;
        $this->ua = $ua;
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        $client = new Client();

        if (!empty($this->ua)) {
            $client->setHeader('user-agent', $this->ua);
        }

        $crawler = $client->request('GET', self::URL);
        $form = $crawler->selectButton('Sign In')->form();
        $crawler = $client->submit($form, [
            'cardNumber' => $this->card->getNumber(),
            'expMonth' => $this->card->getMonth(),
            'expYear' => $this->card->getYear(),
            'cvv' => $this->card->getCvv(),
        ]);

        $balance = trim($crawler->filter('#Avlbal')->text());

        $balance = preg_replace('|[^0-9,.]|', '', $balance);

        return floatval($balance);
    }
}