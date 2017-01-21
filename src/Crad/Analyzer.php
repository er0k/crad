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
                $card->showInfo();
                $sheet = $this->storage->findBalanceSheet($id);

                if ($sheet) {
                    $sheet->showInfo();
                } else {
                    echo "no balance sheet for this card\n";
                }
            }

            echo "--------------------\n";
        }
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