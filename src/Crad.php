<?php

use Crad\BalanceChecker;
use Crad\Card;
use Crad\CardException;
use Crad\EncryptedStorage;
use Crad\EncryptedStorageException;
use Crad\Reader;
use Crad\ReaderException;
use Seld\CliPrompt\CliPrompt;

class Crad
{
    /** @var Reader */
    private $reader;

    /** @var EncryptedStorage */
    private $storage;

    /** @var Card */
    private $card;

    /** @var Card */
    private $storedCard;

    public function __construct()
    {
        $this->reader = new Reader();
        $this->storage = new EncryptedStorage();
    }

    public function run()
    {
        $this->getCard();

        try {
            $this->parseInput();
        } catch (ReaderException $re) {
            echo $re->getMessage();
            echo $re->getTraceAsString();
        }

        try {
            $this->handleCard();
        } catch (CardException $ce) {
            echo $ce->getMessage();
            echo $ce->getTraceAsString();
        } catch (EncryptedStorageException $ese) {
            echo $ese->getMessage();
            echo $ese->getTraceAsString();
        }

        try {
            $this->checkBalance();
        } catch (BalanceCheckerException $bce) {
            echo $bce->getMessage();
            echo $bce->getTraceAsString();
        }

        $this->save();
    }

    private function getCard($forceNew = false)
    {
        if ($this->shouldGetNewCard() || $forceNew) {
            echo "----- new card -----\n";
            $this->card = new Card();
            $this->storedCard = null;
        }

        return $this->card;
    }

    private function parseInput()
    {
        $this->reader->setCard($this->card);

        // read from STDIN until ctrl+d or empty line
        while ($line = CliPrompt::hiddenPrompt()) {
            $this->reader->read($line);
        }

        return $this;
    }

    private function shouldGetNewCard()
    {
        if (!$this->card) {
            return true;
        }

        if ($this->card->hasAllData()) {
            return true;
        }

        return false;
    }

    /**
     * @return Crad
     */
    private function handleCard()
    {
        $this->card->showInfo();

        if ($this->findStoredCard()) {
            return $this->handleStoredCard();
        }

        if ($this->card->hasAllData()) {
            $this->storage->insert($this->card);
        }

        return $this;
    }

    /**
     * @return Crad
     */
    private function handleStoredCard()
    {
        $this->storedCard->showInfo();

        if ($this->card->hasAllData()) {
            if ($this->card->hasCardChanged($this->storedCard)) {
                $this->storage->update($this->card);
            }
        } else {
            $this->card = $this->storedCard;
        }

        return $this;
    }

    /**
     * @return Crad
     */
    private function checkBalance()
    {
        if ($this->card->hasAllData()) {
            echo 'checking balance...';
            $checker = new BalanceChecker($this->card);

            $balance = $checker->checkPreviousBalance()->getCurrentBalance();

            echo money_format('$%i', $balance) . "\n\n";
        }

        return $this;
    }

    /**
     * @return Crad
     */
    private function save()
    {
        // save
        return $this;
    }

    /**
     * @param  Card $card
     * @return Card | null
     */
    private function findStoredCard()
    {
        if (!$this->storedCard) {
            $this->storedCard = $this->storage->findCard($this->card->getHash());
        }

        if ($this->storedCard) {
            echo "got card from storage\n";
        } else {
            echo "card not stored\n";
        }

        return $this->storedCard;
    }
}