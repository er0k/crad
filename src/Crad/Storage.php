<?php

namespace Crad;

class Storage
{

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
}