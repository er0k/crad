<?php

namespace Crad;

class Reader
{
    /** @var Card */
    private $card;


    /**
     * @param Card | null $card
     */
    public function __construct(Card $card = null)
    {
        if (!$card) {
            $card = new Card();
        }

        $this->card = $card;
    }

    /**
     * @param Card $card
     */
    public function setCard(Card $card)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * @param  string $input
     * @return void
     */
    public function read($input = '')
    {
        $this->readInput($input)
            ->setCardData();
    }

    /**
     * @param string $input
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
            throw new ReaderException("Tracks full");
        }

        $this->card->setTrack($num, $data);
    }

}