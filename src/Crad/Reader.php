<?php

namespace Crad;

class Reader
{
    /** @var Card */
    private $card;

    /** @var Card */
    private $storedCard;

    /** @var BalanceChecker */
    private $balanceChecker;

    /** @var EncryptedStorage */
    private $storage;


    public function __construct()
    {
        $this->storage = new EncryptedStorage();
    }

    /**
     * @param  string $input
     * @return void
     */
    public function read($input = '')
    {
        $this->getCard();

        $this->readInput($input);

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
     * @param string $input
     * @throws Exception
     * @return Card
     */
    private function readInput($input)
    {
        foreach ([1,2] as $trackNum) {
            if (
                ($data = $this->card->isTrack($trackNum, $input))
                && !$this->hasAllTracks()
            ) {
                $this->addTrackData($trackNum, $data);
                $input = '';
            }
        }

        if (!empty($input)) {
            $this->parseExtraInput($input);
        }

        $this->setCardData();

        return $this;
    }

    /**
     * @param  string $input
     * @return Card
     */
    private function parseExtraInput($input)
    {
        $input = trim($input);

        if (strlen($input) == 3 && is_numeric($input)) {
            $this->card->setCvv($input);
        }

        return $this;
    }

    /**
     * @return Card
     */
    private function setCardData()
    {
        $this->card->setNumber($this->card->getNumber());
        $this->card->setDate($this->card->getDate());
        $this->card->setCvv($this->card->getCvv());
        $this->card->setName($this->card->getName());
        $this->card->setHash($this->card->getHash());

        return $this;
    }

    /**
     * @return bool
     */
    private function hasAllTracks()
    {
        if (count($this->card->getTracks()) == 2) {
            return true;
        }

        return false;
    }

    /**
     * @param int $num
     * @param array $data
     */
    private function addTrackData($num, $data)
    {
        if ($this->hasAllTracks()) {
            throw new Exception("Tracks full");
        }

        $this->card->setTrack($num, $data);
    }

    /**
     * @param  Card $card
     * @return Card
     */
    private function findCard()
    {
        $this->storedCard = $this->storage->findCard($this->card->getHash());

        if ($this->storedCard) {
            echo "getting card from storage\n";
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

        return true;
    }

    private function save()
    {
        if ($this->storedCard) {
            // $result = $this->storage->update($this->card);
            $result = 'no action needed';
        } else {
            $result = $this->storage->insert($this->card);
        }

        print_r(compact('result'));

        return true;
    }
}