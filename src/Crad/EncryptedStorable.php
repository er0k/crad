<?php

namespace Crad;

interface EncryptedStorable
{
    /**
     * @return string
     */
    public function getHash();

    /**
     * @return bool
     */
    public function hasAllData();

    /**
     * @param  stdClass $data
     * @return EncryptedStorable
     */
    public function hydrate($data);
}