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
     * @return string | null
     */
    public function read($input = '')
    {
        $input = trim($input);

        $input = $this->readCard($input);
        $input = $this->readCvv($input);

        $this->setCardData();

        return $this->readCommand($input);
    }

    /**
     * @param string $input
     * @return string | null
     */
    private function readCard($input)
    {
        if (!$input) {
            return null;
        }

        foreach ([1,2] as $trackNum) {
            if (
                ($data = $this->card->isTrack($trackNum, $input))
                && !$this->hasAllTracks()
            ) {
                $this->addTrackData($trackNum, $data);

                return null;
            }
        }

        return $input;
    }

    /**
     * @param  string $input
     * @return string | null
     */
    private function readCommand($input)
    {
        if (!$input) {
            return null;
        }

        if (substr($input, 0, 1) === "!") {
            return strtolower(substr_replace($input, '', 0, 1));
        }

        throw new ReaderException("Could not read input");
    }

    /**
     * @param  string $input
     * @return string | null
     */
    private function readCvv($input)
    {
        if (!$input) {
            return null;
        }

        if (strlen($input) == 3 && is_numeric($input)) {
            $this->card->setCvv($input);

            return null;
        }

        return $input;
    }

    private function setCardData()
    {
        $this->card->setNumber($this->card->getNumber());
        $this->card->setDate($this->card->getDate());
        $this->card->setCvv($this->card->getCvv());
        $this->card->setName($this->card->getName());
        $this->card->setHash($this->card->getHash());
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