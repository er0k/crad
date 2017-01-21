<?php

use Crad\Analyzer;
use Crad\BalanceChecker;
use Crad\BalanceCheckerException;
use Crad\BalanceSheet;
use Crad\BalanceSheetException;
use Crad\Card;
use Crad\CardException;
use Crad\Config;
use Crad\EncryptedStorage;
use Crad\EncryptedStorageException;
use Crad\Reader;
use Crad\ReaderException;
use Seld\CliPrompt\CliPrompt;

class Crad
{
    private $config;

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
        $this->config = new Config();
        $this->storage = new EncryptedStorage($this->config);
        $this->reader = new Reader();
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
        } catch (BalanceCheckerException $bce) {
            $this->handleError($bce);
        }
    }

    /**
     * @param  bool $forceNew
     */
    private function initialize($forceNew = false)
    {
        if ($this->shouldGetNewCard() || $forceNew) {
            $this->card = new Card();
            $this->storedCard = null;
            $this->balanceSheet = new BalanceSheet();
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

        echo "crad$ ";

        // read from STDIN until ctrl+d or empty line
        while ($line = CliPrompt::hiddenPrompt()) {
            $command = $this->reader->read($line);
            if ($command) {
                return $this->execute($command);
            }
        }

        return $this;
    }

    private function execute($cmd)
    {
        switch ($cmd) {
            case 'help':
            case 'h':
                return $this->showHelp();
            case 'quit':
            case 'q':
                die("Bye\n");
            case 'total':
            case 't':
                return $this->calculateTotal();
            case 'new':
            case 'n':
                return $this->initialize(true);
            case 'count':
            case 'c':
                return $this->count();
            case 'show':
            case 's':
                $this->card->showInfo();
                $this->balanceSheet->showInfo();
                return;
            case 'break':
                return;
            default:
                echo "$cmd command not yet implemented\n";
                return;
        }
    }

    private function showHelp()
    {
        echo "commands:\n";
        echo "!help\tshow this message\n";
        echo "!quit\texit\n";
        echo "!total\ttotal up all balances\n";
        echo "!count\tcount all the cards and balance sheets\n";
        echo "!show\tshow info of current card and balance sheet\n";
        echo "\n";
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
        if ($this->card->hasAllData()) {
            if ($this->card->hasCardChanged($this->storedCard)) {
                $this->storage->update($this->card);
            }
        } else {
            $this->card = $this->storedCard;
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

        if (
            $this->balanceSheet->hasAllData()
            && $this->balanceSheet->hasChanged($this->storedBalanceSheet)
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

    private function calculateTotal()
    {
        echo 'calculating total...';

        $anal = new Analyzer($this->storage);

        $total = $anal->getTotal();

        echo money_format('$%i', $total) . "\n";
    }

    private function count()
    {
        $anal = new Analyzer($this->storage);

        $anal->countCardsAndSheets();
    }

    /**
     * @return Card | null
     */
    private function findStoredCard()
    {
        if (!$this->storedCard) {
            $this->storedCard = $this->storage->findCard($this->card->getHash());
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

        return $this->storedBalanceSheet;
    }

    private function handleError($error)
    {
        echo $error->getMessage();
        echo "\n";
        echo $error->getTraceAsString();
        echo "\n";
    }
}