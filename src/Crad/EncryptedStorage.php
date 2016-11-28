<?php

namespace Crad;

use Crad\Card;
use Crad\Exception;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use medoo;

class EncryptedStorage
{
    /** @var string */
    private $key;

    /** @var meedo */
    private $db;

    const KEY_FILE = '/home/er0k/.www/cradkey';

    const DB_FILE = 'data/crad.db';

    const TABLE = 'cards';

    public function __construct()
    {
        $this->key = file_get_contents(self::KEY_FILE);
        $this->db = $this->getDb();
    }

    /**
     * @param  string $id
     * @return Card | null
     */
    public function findCard($id)
    {
        echo "finding...";

        $storedData = $this->db->get(self::TABLE,
            ['id', 'data'],
            ['id' => $id]
        );

        if ($storedData) {
            return $this->decrypt($storedData['data']);
        }

        return null;
    }

    /**
     * @param  Card $data
     * @return int | false
     */
    public function insert(Card $card)
    {
        if (!$card->hasAllData()) {
            throw new Exception("Cannot save card without all data");
        }

        $encryptedData = $this->encrypt($card);

        echo "inserting...";

        return $this->db->insert(self::TABLE,
            ['id' => $card->getHash(), 'data' => $encryptedData]
        );
    }

    /**
     * @param  Card $data
     * @return int | false
     */
    public function update(Card $card)
    {
        if (!$card->hasAllData()) {
            throw new Exception("Cannot update card without all data");
        }

        $encryptedCard = $this->encrypt($card);

        echo "updating...";

        return $this->db->update(self::TABLE,
            ['data' => $encryptedCard],
            ['id' => $card->getHash()]
        );
    }

    /**
     * @param  Card $data
     * @return string
     */
    private function encrypt(Card $card)
    {
        return Crypto::encrypt(json_encode($card), $this->getKey());
    }

    /**
     * @param  string $encryptedData
     * @return Card
     */
    private function decrypt($encryptedData)
    {
        // this will throw a \Defuse\Crypto\WrongKeyOrModifiedCiphertextException
        // if the wrong key was used or
        // the encrypted data was modified/corrupted
        $data = Crypto::decrypt($encryptedData, $this->getKey());

        return new Card(json_decode($data));
    }

    /**
     * @return Key
     */
    private function getKey()
    {
        return Key::loadFromAsciiSafeString($this->key);
    }

    private function getDb()
    {
        if ($this->db) {
            return $this->db;
        }

        return new medoo([
            'database_type' => 'sqlite',
            'database_file' => $this->getDbFile()
        ]);
    }

    private function getDbFile()
    {
        if (!is_file(self::DB_FILE)) {
            touch(self::DB_FILE);
        }

        if (!is_writable(self::DB_FILE)) {
            throw new Exception("Can't write to database");
        }

        return self::DB_FILE;
    }
}
