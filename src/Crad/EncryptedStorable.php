<?php

namespace Crad;

interface EncryptedStorable
{
    /**
     * @return string
     */
    public function getHash();
}