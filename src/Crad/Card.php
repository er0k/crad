<?php

namespace Crad;

class Card
{
    /** @var string */
    private $number;

    /** @var string */
    private $date;

    /** @var string */
    private $cvv;

    /** @var string */
    private $name;

    /** @var array */
    private $tracksData;

    const T1_PATTERN = "^%B([^\^\W]{0,19})\^([^\^]{2,26})\^(\d{4})(\w{3})[^?]+\?\w?$";
    
    const T2_PATTERN = "^;([^=]{0,19})=(\d{4})(\w{3})[^?]+\?\w?$";


    /**
     * @param string $input
     * @throws Exception 
     * @return Card
     */
    public function readInput($input)
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

        $this->parseTrackData();

        if (!empty($input)) {
            $this->parseExtraInput($input);
        }

        return $this;
    }

    /**
     * @return Card
     */
    public function showInfo()
    {
        print_r([
            'name' => $this->getName(),
            'number' => $this->getNumber(),
            'date' => $this->getDate(),
            'cvv' => $this->getCvv(),
        ]);

        return $this;
    }

    /**
     * @return bool
     */
    public function hasAllData()
    {
        if (!$this->hasNumber()) {
            return false;
        }

        if (!$this->hasDate()) {
            return false;
        }

        if (!$this->hasCvv()) {
            return false;
        }

        if (!$this->hasName()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        if ($this->number) {
            return $this->number;
        }

        if (isset($this->tracksData[1])) {
            return $this->tracksData[1][1];
        }

        if (isset($this->tracksData[2])) {
            return $this->tracksData[2][1];
        }
    }

    /**
     * @param string $date 'YYMM'
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        if ($this->date) {
            return $this->date;
        }

        if (isset($this->tracksData[1])) {
            return $this->tracksData[1][3];
        }

        if (isset($this->tracksData[2])) {
            return $this->tracksData[2][2];
        }
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        if ($this->name) {
            return $this->name;
        }

        if (isset($this->tracksData[1])) {
            return $this->tracksData[1][2];
        }
    }

    /**
     * @param string $cvv
     */
    public function setCvv($cvv)
    {
        $this->cvv = $cvv;
    }

    /**
     * @return string
     */
    public function getCvv()
    {
        if ($this->cvv) {
            return $this->cvv;
        }
    }

    /**
     * @param  string $input
     * @return Card
     */
    private function parseExtraInput($input)
    {
        $input = trim($input);

        if (strlen($input) == 3 && is_numeric($input)) {
            $this->setCvv($input);
        }

        return $this;
    }

    /**
     * @return bool
     */
    private function hasNumber()
    {
        return !is_null($this->number);
    }

    /**
     * @return bool
     */
    private function hasDate()
    {
        return !is_null($this->date);
    }

    /**
     * @return bool
     */
    private function hasCvv()
    {
        return !is_null($this->cvv);
    }

    /**
     * @return bool
     */
    private function hasName()
    {
        return !is_null($this->name);
    }

    /**
     * @return bool
     */
    private function hasAllTracks()
    {
        if (count($this->tracksData) == 2) {
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

        $this->tracksData[$num] = $data;
    }

    /**
     * @param  int $num
     * @param  string $track
     * @return false | array
     */
    private function isTrack($num, $track)
    {
        switch ($num) {
            case 1: $pattern = self::T1_PATTERN; break;
            case 2: $pattern = self::T2_PATTERN; break;
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
    private function parseTrackData()
    {
        $this->setNumber($this->getNumber());
        $this->setDate($this->getDate());
        $this->setCvv($this->getCvv());
        $this->setName($this->getName());

        return $this;
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
}