<?php

use Crad\Analyzer;
use Crad\BalanceChecker;
use Crad\BalanceCheckerException;
use Crad\BalanceSheet;
use Crad\BalanceSheetException;
use Crad\Card;
use Crad\CardException;
use Crad\Commander;
use Crad\Config;
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
        } catch (EncryptedStorageException $ese) {
            $this->handleError($ese);
        } catch (BalanceCheckerException $bce) {
            $this->handleError($bce);
        }
    }

    /**
     * @param  bool $forceNew
     */
    public function initialize($forceNew = false)
    {
        if ($this->shouldGetNewCard() || $forceNew) {
            $this->card = new Card();
            $this->balanceSheet = new BalanceSheet();
        }
    }

    /**
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }

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
        $this->reader->setCard($this->card);

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
                $this->storage->update($this->card);
            }
        } else {
            $this->card = $storedCard;
        }

        if ($this->card->hasAllData()) {
            $this->card->showInfo();
        }

        return $this;
    }

    /**
     * @return Crad
     */
    private function handleBalanceSheet()
    {
        $storedBalanceSheet = $this->findStoredBalanceSheet();
        
        if ($$storedBalanceSheet) {
            return $this->handleStoredBalanceSheet($storedBalanceSheet);
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
     * @param BalanceSheet $storedBalanceSheet
     * @return Crad
     */
    private function handleStoredBalanceSheet(BalanceSheet $storedBalanceSheet)
    {
        $storedBalanceSheet->showInfo();

        $this->balanceSheet = $storedBalanceSheet;

        echo "Check most current balance? y/n\n";

        if (CliPrompt::prompt() == 'y') {
            $this->checkBalance();
        }

        if (
            $this->balanceSheet->hasAllData()
            && $this->balanceSheet->hasChanged($storedBalanceSheet)
        ) {
            $this->storage->update($this->balanceSheet);
        }

        return $this;
    }

    private function checkBalance()
    {
        echo 'checking balance...';

        $checker = new BalanceChecker($this->card);

        $this->balanceSheet = $checker->getBalanceSheet();

        $checker->compareBalanceToTransactionTotal($this->balanceSheet);

        $this->balanceSheet->showInfo();
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
        echo $error->getMessage();
        echo "\n";
        echo $error->getTraceAsString();
        echo "\n";
    }
}