<?php

namespace Crad;

class Card implements \JsonSerializable, EncryptedStorable
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

    /** @var string */
    private $hash;

    const TRACK_ONE = "^%B([^\^\W]{0,19})\^([^\^]{2,26})\^(\d{4})(\w{3})[^?]+\?\w?$";
    const TRACK_TWO = "^;([^=]{0,19})=(\d{4})(\w{3})[^?]+\?\w?$";

    const SHOW_OUTPUT = true;


    /**
     * @return Card
     */
    public function showInfo()
    {
        if (self::SHOW_OUTPUT) {
            print_r([
                'name' => $this->getName(),
                'number' => $this->getNumber(),
                'date' => $this->getDate(),
                'cvv' => $this->getCvv(),
                #'hash' => $this->getHash(),
            ]);
        }

        return $this;
    }

    public function checkDate()
    {
        if (!$this->hasDate()) {
            return;
        }

        $date = \DateTime::createFromFormat('ym', $this->getDate());
        $diff = $date->diff(new \DateTime());

        $years = $diff->format('%y');
        $months = $diff->format('%m');

        $expiresInMonths = ($years * 12) + $months;

        if ($expiresInMonths > 1) {
            echo "this card expires in $expiresInMonths months\n";
        }

        if ($expiresInMonths < 1 && $expiresInMonths > 0) {
            echo "this card is about to expire!\n";
        }

        if ($expiresInMonths < 0) {
            echo "this card expired " . abs($expiresInMonths) . " months ago\n";
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasAllData()
    {
        if (!$this->hasTrack(1)) {
            return false;
        }

        if (!$this->hasTrack(2)) {
            return false;
        }

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

        if (!$this->hasHash()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function hasCardChanged(Card $card)
    {
        if (!$card->hasAllData() || !$this->hasAllData()) {
            throw new CardException("Cannot compare cards without all data");
        }

        if (json_encode($card) === json_encode($this)) {
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

        if ($this->hasTrack(1)) {
            return $this->tracks[1][1];
        }

        if ($this->hasTrack(2)) {
            return $this->tracks[2][1];
        }
    }

    /**
     * @return bool
     */
    public function hasNumber()
    {
        return !is_null($this->number);
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
        if ($this->hasDate()) {
            return $this->date;
        }

        if ($this->hasTrack(1)) {
            return $this->tracks[1][3];
        }

        if ($this->hasTrack(2)) {
            return $this->tracks[2][2];
        }
    }

    /**
     * @return bool
     */
    public function hasDate()
    {
        return !is_null($this->date);
    }

    /**
     * @return string MM
     */
    public function getMonth()
    {
        return substr($this->getDate(), 2, 2);
    }

    /**
     * @return string YY
     */
    public function getYear()
    {
        return substr($this->getDate(), 0, 2);
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

        if ($this->hasTrack(1)) {
            return $this->tracks[1][2];
        }
    }

    /**
     * @return bool
     */
    public function hasName()
    {
        return !is_null($this->name);
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
        return $this->cvv;
    }

    /**
     * @return bool
     */
    public function hasCvv()
    {
        return !is_null($this->cvv);
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
     * @param array | object
     */
    public function setTracks($tracks)
    {
        foreach ($tracks as $num => $track) {
            if ($this->isTrack($num, $track)) {
                $this->setTrack($num, $track);
            }
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
     * @param  int  $num
     * @return bool
     */
    public function hasTrack($num)
    {
        return isset($this->tracks[$num]);
    }

    /**
     * @param  int $num
     * @param  string $track
     * @return false | array
     */
    public function isTrack($num, $track)
    {
        if (is_array($track)) {
            $track = $track[0];
        }

        switch ($num) {
            case 1: $pattern = self::TRACK_ONE; break;
            case 2: $pattern = self::TRACK_TWO; break;
            default:
                return false;
        }

        if (!($data = $this->isMatch($track, $pattern))) {
            return false;
        }

        return $data;
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
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        if ($this->hasHash()) {
            return $this->hash;
        }

        if ($this->hasNumber() && $this->hasDate()) {
            return hash('sha512', $this->getNumber() . $this->getDate());
        }
    }

    /**
     * @return bool
     */
    public function hasHash()
    {
        return !is_null($this->hash);
    }

    /**
     * @param  stdClass $data
     * @return void
     */
    public function hydrate($data)
    {
        if (is_null($data)) {
            return;
        }

        if (
            isset($data->number)
            && isset($data->date)
            && isset($data->cvv)
            && isset($data->name)
            && isset($data->tracks)
        ) {
            $this->setTracks($data->tracks);
            $this->setNumber($data->number);
            $this->setDate($data->date);
            $this->setCvv($data->cvv);
            $this->setName($data->name);

            $this->setHash($this->getHash());
        }

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'number' => $this->getNumber(),
            'date' => $this->getDate(),
            'cvv' => $this->getCvv(),
            'name' => $this->getName(),
            'tracks' => $this->getTracks(),
            'hash' => $this->getHash(),
        ];
    }
}
