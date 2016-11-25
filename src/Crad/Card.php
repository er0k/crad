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
    private $tracks;


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

        if (isset($this->tracks[1])) {
            return $this->tracks[1][1];
        }

        if (isset($this->tracks[2])) {
            return $this->tracks[2][1];
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

        if (isset($this->tracks[1])) {
            return $this->tracks[1][3];
        }

        if (isset($this->tracks[2])) {
            return $this->tracks[2][2];
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

        if (isset($this->tracks[1])) {
            return $this->tracks[1][2];
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
     * @return array
     */
    public function getTracks()
    {
        return $this->tracks;
    }

    /**
     * @param int $num
     * @param string $track
     */
    public function setTrack($num, $track)
    {
        $this->tracks[$num] = $track;
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
}
