<?php

use Crad\Analyzer;
use Crad\BalanceChecker;
use Crad\BalanceSheet;
use Crad\Card;
use Crad\Commander;
use Crad\EncryptedStorage;
use Crad\Exception;
use Crad\Reader;
use Seld\CliPrompt\CliPrompt;

class Crad
{
    /** @var Reader */
    private $reader;

    /** @var EncryptedStorage */
    private $storage;

    /** @var Card */
    private $card;

    /** @var BalanceSheet */
    private $balanceSheet;

    public function __construct()
    {
        $this->storage = new EncryptedStorage();
        $this->reader = new Reader();
        $this->commander = new Commander($this);
    }

    public function run()
    {
        try {
            $this->initialize()
                ->parseInput()
                ->handleCard()
                ->handleBalanceSheet();
            } catch (Exception $e) {
                $this->handleError($e);
            }
    }

    /**
     * @param   bool $forceNew
     * @return  Crad
     */
    public function initialize($forceNew = false)
    {
        if ($this->shouldGetNewCard() || $forceNew) {
            $this->card = new Card();
            $this->balanceSheet = new BalanceSheet();
        }

        $this->reader->setCard($this->card);

        return $this;
    }

    /**
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @param Card $card
     */
    public function setCard(Card $card)
    {
        $this->card = $card;
    }

    /**
     * @return BalanceSheet
     */
    public function getBalanceSheet()
    {
        return $this->balanceSheet;
    }

    /**
     * @return EncryptedStorage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @return Crad
     * @throws RuntimeException
     * @throws ReaderException
     */
    private function parseInput()
    {
        echo "crad$ ";

        // read from STDIN until ctrl+d or empty line
        while ($line = CliPrompt::hiddenPrompt()) {
            $command = $this->reader->read($line);
            if ($command) {
                return $this->commander->execute($command);
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
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
        $this->card->checkDate();

        $storedCard = $this->findStoredCard();

        if ($storedCard) {
            return $this->handleStoredCard($storedCard);
        }

        if ($this->card->hasAllData()) {
            $this->storage->insert($this->card);
        }

        return $this;
    }

    /**
     * @param Card $storedCard
     * @return Crad
     */
    private function handleStoredCard(Card $storedCard)
    {
        if ($this->card->hasAllData()) {
            if ($this->card->hasCardChanged($storedCard)) {
                $storedCard->showInfo();
                $this->storage->update($this->card);
            }
        } else {
            $this->card = $storedCard;
        }

        return $this;
    }

    /**
     * @return Crad
     */
    private function handleBalanceSheet()
    {
        $storedBalanceSheet = $this->findStoredBalanceSheet();

        if ($storedBalanceSheet) {
            return $this->handleStoredBalanceSheet($storedBalanceSheet);
        }

        if ($this->card->hasAllData()) {
            $balanceSheet = $this->checkBalance();
            if ($balanceSheet->hasAllData()) {
                $this->storage->insert($balanceSheet);
            }
            $this->balanceSheet = $balanceSheet;
        }

        return $this;
    }

    /**
     * @param BalanceSheet $storedBalanceSheet
     * @return Crad
     */
    private function handleStoredBalanceSheet(BalanceSheet $storedBalanceSheet)
    {
        $storedBalanceSheet->showInfo();

        echo "Check most current balance? y/n\n";

        if (CliPrompt::prompt() == 'y') {
            $this->balanceSheet = $this->checkBalance();
        }

        if ($this->balanceSheet->hasAllData()) {
            if ($this->balanceSheet->hasChanged($storedBalanceSheet)) {
                $this->storage->update($this->balanceSheet);
            }
        } else {
            $this->balanceSheet = $storedBalanceSheet;
        }

        return $this;
    }

    /**
     * @return BalanceSheet
     */
    private function checkBalance()
    {
        echo 'checking balance...';

        $checker = new BalanceChecker($this->card);

        $balanceSheet = $checker->getBalanceSheet();

        $checker->compareBalanceToTransactionTotal($balanceSheet);

        $balanceSheet->showInfo();

        return $balanceSheet;
    }

    /**
     * @return Card | null
     */
    private function findStoredCard()
    {
        $hash = $this->card->getHash();

        $storedCard = $this->storage->findCard($hash);

        return $storedCard;
    }

    /**
     * @return BalanceSheet | null
     */
    private function findStoredBalanceSheet()
    {
        $hash = $this->card->getHash();

        $storedBalanceSheet = $this->storage->findBalanceSheet($hash);

        return $storedBalanceSheet;
    }

    /**
     * @param  Exception $error
     */
    private function handleError(Exception $error)
    {
        echo $error;
        echo "\n\n";
    }
}