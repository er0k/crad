<?php

use Crad\BalanceChecker;
use Crad\BalanceCheckerException;
use Crad\BalanceSheet;
use Crad\BalanceSheetException;
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

    /** @var BalanceSheet */
    private $balanceSheet;

    /** @var BalanceSheet */
    private $storedBalanceSheet;

    public function __construct()
    {
        $this->reader = new Reader();
        $this->storage = new EncryptedStorage();
    }

    public function run()
    {
        $this->initialize();

        try {
            $this->parseInput();
        } catch (ReaderException $re) {
            $this->handleError($re);
        }

        try {
            $this->handleCard();
        } catch (CardException $ce) {
            $this->handleError($ce);
        } catch (EncryptedStorageException $ese) {
            $this->handleError($ese);
        }

        try {
            $this->handleBalanceSheet();
        } catch (BalanceSheetException $bse) {
            $this->handleError($bse);
        } catch (EncryptedStorableException $ese) {
            $this->handleError($ese);
        }
    }

    /**
     * @param  bool $forceNew
     */
    private function initialize($forceNew = false)
    {
        if ($this->shouldGetNewCard() || $forceNew) {
            echo "\n----- new card -----\n";
            $this->card = new Card();
            $this->storedCard = null;
            $this->balanceSheet = null;
            $this->storedBalanceSheet = null;
        }
    }

    /**
     * @return Crad
     * @throws RuntimeException
     * @throws ReaderException
     */
    private function parseInput()
    {
        $this->reader->setCard($this->card);

        // read from STDIN until ctrl+d or empty line
        while ($line = CliPrompt::hiddenPrompt()) {
            $this->reader->read($line);
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
    private function handleBalanceSheet()
    {
        if ($this->findStoredBalanceSheet()) {
            return $this->handleStoredBalanceSheet();
        }

        if ($this->card->hasAllData()) {
            $this->checkBalance();
            if ($this->balanceSheet->hasAllData()) {
                $this->storage->insert($this->balanceSheet);
            }
        }

        return $this;
    }

    /**
     * @return Crad
     */
    private function handleStoredBalanceSheet()
    {
        $this->storedBalanceSheet->showInfo();

        $this->balanceSheet = $this->storedBalanceSheet;

        echo "Check most current balance? y/n\n";

        if (CliPrompt::prompt() == 'y') {
            $this->checkBalance();
        }

        if ($this->balanceSheet->hasAllData()) {
            if ($this->balanceSheet->hasChanged($this->storedBalanceSheet)) {
                $this->storage->update($this->balanceSheet);
            }
        }

        return $this;
    }

    private function checkBalance()
    {
        echo 'checking balance...';

        $checker = new BalanceChecker($this->card);

        $this->balanceSheet = $checker->getBalanceSheet();

        $this->balanceSheet->showInfo();
    }

    /**
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

    /**
     * @return BalanceSheet | null
     */
    private function findStoredBalanceSheet()
    {
        if (!$this->storedBalanceSheet) {
            $this->storedBalanceSheet = $this->storage->findBalanceSheet($this->card->getHash());
        }

        if ($this->storedBalanceSheet) {
            echo "got balance sheet from storage\n";
        } else {
            echo "balance sheet not stored\n";
        }

        return $this->storedBalanceSheet;
    }

    private function handleError($error)
    {
        echo $error->getMessage();
        echo "\n";
        echo $error->getTraceAsString();
    }
}