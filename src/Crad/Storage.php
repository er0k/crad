<?php

namespace Crad;

use Defuse\Crypto;

class Storage
{

    const KEY_FILE = '/home/er0k/.www/cradkey';

    public function __construct()
    {
        $this->key = file_get_contents(self::KEY_FILE);
    }

    /**
     * @param  Card $card
     * @return Card | null
     */
    public function findCard(Card $card)
    {
        return null;
    }

    /**
     * @param  Card $card
     * @throws Exception if save fails
     * @return bool
     */
    public function saveCard(Card $card)
    {
	$encryptedCard = $this->encrypt($card);

        return true;
    }

    /**
     * @param  Card $card
     * @throws Exception if update fails
     * @return bool
     */
    public function updateBalance(Card $card)
    {
        return true;
    }

    public function encrypt(Card $card)
    {
	return $card;
    }
}
