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

    const TRACK_ONE = "^%B([^\^\W]{0,19})\^([^\^]{2,26})\^(\d{4})(\w{3})[^?]+\?\w?$";

    const TRACK_TWO = "^;([^=]{0,19})=(\d{4})(\w{3})[^?]+\?\w?$";


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
                ($data = $this->isTrack($trackNum, $input))
                && !$this->hasAllTracks()
            ) {
                $this->addTrackData($trackNum, $data);
                $input = '';
            }
        }

        $this->setCardData();

        if (!empty($input)) {
            $this->parseExtraInput($input);
        }

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
     * @param  int $num
     * @param  string $track
     * @return false | array
     */
    private function isTrack($num, $track)
    {
        switch ($num) {
            case 1: $pattern = self::TRACK_ONE; break;
            case 2: $pattern = self::TRACK_TWO; break;
            default:
                throw new Exception("Only two tracks are supported");
        }

        if (!$data = $this->isMatch($track, $pattern)) {
            return false;
        }

        return $data;
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
     * @param  string $subject
     * @param  string $pattern
     * @return false | array
     */
    private function isMatch($subject, $pattern)
    {
        if (preg_match('|' . $pattern . '|',  $subject, $matches) !== 1) {
            return false;
        }

        return $matches;
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