<?php

namespace Crad;

use Seld\CliPrompt\CliPrompt;

class Analyzer
{
    /** @var EncryptedStorage */
    private $storage;

    /**
     * @param EncryptedStorage $storage
     */
    public function __construct(EncryptedStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        $sheetIds = $this->storage->getBalanceSheetIds();

        $total = 0;

        foreach ($sheetIds as $id) {
            $sheet = $this->storage->findBalanceSheet($id);
            $balance = $sheet->getBalance();

            $total += $balance;
        }

        return $total;
    }

    public function countCardsAndSheets()
    {
        $numCards = $this->storage->countCards();

        $numSheets = $this->storage->countBalanceSheets();

        print_r(compact('numCards', 'numSheets'));

        if ($numSheets < $numCards) {
            echo "there are less balance sheets than cards!\n";
            $this->findMissingSheets();
        }
    }

    public function showBalances()
    {
        $cardIds = $this->storage->getCardIds();

        $cards = array();

        foreach ($cardIds as $i => $id) {
            $card = $this->storage->findCard($id);

            if (!$card) {
                continue;
            }

            $sheet = $this->storage->findBalanceSheet($id);

            if (!$sheet) {
                continue;
            }

            if ($sheet->getBalance() == 0) {
                continue;
            }

            $cards[] = array(
                'card' => $card,
                'sheet' => $sheet,
            );

            echo $i;
        }

        usort($cards, function($a, $b) {
            $bal1 = $a['sheet']->getBalance();
            $bal2 = $b['sheet']->getBalance();
            if ($bal1 == $bal2) {
                return 0;
            }

            return ($bal1 < $bal2) ? -1 : 1;
        });

        foreach ($cards as $card) {
            $card['card']->showInfo();
            $card['sheet']->showInfo();
            echo "----------------------------\n";
        }
    }

    public function refreshBalances()
    {
        $cardIds = $this->storage->getCardIds();

        echo "refreshing " . count($cardIds) . " cards\n";

        foreach ($cardIds as $key => $id) {
            echo "refreshing $key...";

            $card = $this->storage->findCard($id);

            if (!$card) {
                continue;
            }

            $storedSheet = $this->storage->findBalanceSheet($id);

            if ($storedSheet && $storedSheet->getBalance() == 0) {
                echo  "zero balance, skipping\n";
                continue;
            }

            try {
                $checker = new BalanceChecker($card);
                $sheet = $checker->getBalanceSheet();
                if ($sheet->hasAllData()) {
                    $this->storage->update($sheet);
                }
            } catch (BalanceCheckerException $e) {
                echo "Couldn't refresh this one:\n";
                $card->showInfo();
                #throw new AnalyzerException("Couldn't refresh balance", 420, $e);
            }
            echo "done\n";
        }
    }

    /**
     * @param  string $string
     * @return Card | null
     */
    public function search($string)
    {
        $cardIds = $this->storage->getCardIds();

        foreach ($cardIds as $id) {
            $card = $this->storage->findCard($id);
            $found = strpos($card->getNumber(), $string);
            if ($found !== false) {
                return $card;
            }
        }

        echo "not found\n";

        return null;
    }

    private function findMissingSheets()
    {
        $cardIds = $this->storage->getCardIds();

        $sheetIds = $this->storage->getBalanceSheetIds();

        $diff = array_diff($cardIds, $sheetIds);

        if (!empty($diff)) {
            foreach ($diff as $cardId) {
                $card = $this->storage->findCard($cardId);
                $card->showInfo();

                echo "check balance?\n";
                $reply = CliPrompt::prompt();
                if ($reply != 'y') {
                    continue;
                }

                try {
                    $checker = new BalanceChecker($card);
                    $sheet = $checker->getBalanceSheet();
                    if ($sheet->hasAllData()) {
                        $this->storage->update($sheet);
                    }
                } catch (BalanceCheckerException $e) {
                    throw new AnalyzerException("Couldn't check balance", 69, $e);
                }
            }
        }
    }
}