<?php

namespace Crad;

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

    public function getTotal()
    {
        $sheetIds = $this->storage->getBalanceSheetIds();

        $total = 0;

        foreach ($sheetIds as $id) {
            $sheet = $this->storage->findBalanceSheet($id);
            $balance = $sheet->getBalance();

            #print_r(compact('balance', 'id'));

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

        foreach ($cardIds as $id) {
            $card = $this->storage->findCard($id);

            if ($card) {

                $sheet = $this->storage->findBalanceSheet($id);

                if ($sheet) {
                    if ($sheet->getBalance() == 0) {
                        continue;
                    }
                    $card->showInfo();
                    $sheet->showInfo();
                } else {
                    echo "no balance sheet for this card\n";
                }
            }

            echo "--------------------\n";
        }
    }

    public function refreshBalances()
    {
        $cardIds = $this->storage->getCardIds();

        echo "refreshing " . count($cardIds) . " cards\n";

        $i = 1;

        foreach ($cardIds as $id) {
            echo "refreshing $i...";

            $card = $this->storage->findCard($id);

            if ($card) {

                $storedSheet = $this->storage->findBalanceSheet($id);

                if ($storedSheet && $storedSheet->getBalance() == 0) {
                    echo  "zero balance, skipping\n";
                    $i++;
                    continue;
                }

                try {
                    $checker = new BalanceChecker($card);
                    $sheet = $checker->getBalanceSheet();
                    if ($sheet->hasAllData()) {
                        $this->storage->update($sheet);
                    }
                } catch (BalanceCheckerException $e) {
                    echo $e->getMessage() . "\n";
                    $card->showInfo();
                }
            }
            echo "done\n";
            $i++;
        }
    }

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

        return null;
    }

    private function findMissingSheets()
    {
        $cardIds = $this->storage->getCardIds();

        $sheetIds = $this->storage->getBalanceSheetIds();

        $diff = array_diff($cardIds, $sheetIds);

        #print_r(compact('cardIds', 'sheetIds', 'diff'));

        if (!empty($diff)) {
            foreach ($diff as $cardId) {
                $card = $this->storage->findCard($cardId);
                #print_r($card);
                // $checker = new BalanceChecker($card);
                // $sheet = $checker->makeBalanceSheet();
                // $sheet->showInfo();
            }
        }
    }
}