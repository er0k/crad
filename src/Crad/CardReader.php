<?php

namespace Crad;

class CardReader
{
    /** @var Card */
    private $card;

    /** @var Card */
    private $storedCard;

    /** @var BalanceChecker */
    private $balanceChecker;

    /** @var Storage */
    private $storage;


    public function __construct()
    {
        $this->storage = new Storage();
    }

    /**
     * @param  string $input
     * @return void
     */
    public function read($input = '')
    {
        $this->getCard()->readInput($input);

        if (!$this->card->hasAllData()) {
            $this->findCard();
        }

        $this->card->showInfo();

        if ($this->card->hasAllData()) {
            if ($this->checkBalance()) {
                $this->save();
            }
        }
    }

    /**
     * @return Card
     */
    private function getCard()
    {
        if (is_null($this->card) || $this->card->hasAllData()) {
            echo "new card\n";
            $this->card = new Card();
            $this->storedCard = null;
        }

        return $this->card;
    }

    /**
     * @param  Card $card
     * @return Card
     */
    private function findCard()
    {
        $this->storedCard = $this->storage->findCard($this->card);

        if ($this->storedCard) {
            $this->card = $this->storedCard;
        }

        return $this->card;
    }

    /**
     * @return bool
     */
    private function checkBalance()
    {
        echo 'checking balance...';
        $this->balanceChecker = new BalanceChecker($this->card, $this->storage);

        $balance = $this->balanceChecker->checkPreviousBalance()->getCurrentBalance();

        echo money_format('$%i', $balance) . "\n\n";

        return false;
    }

    private function save()
    {
        if (!$this->storedCard) {
            $this->storage->saveCard($this->card);
        }
            
        $this->storage->updateBalance($this->card);

        return true;
    }
}