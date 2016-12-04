<?php

namespace Crad;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\WrongKeyOrModifiedCiphertextException;
use medoo;

class EncryptedStorage
{
    /** @var string */
    private $key;

    /** @var meedo */
    private $db;

    const KEY_FILE = '/home/er0k/.www/cradkey';
    const DB_FILE = 'data/crad.db';
    const CARDS_TABLE = 'cards';
    const SHEETS_TABLE = 'sheets';

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
        return $this->find(self::CARDS_TABLE, $id);
    }

    /**
     * @param  string $id
     * @return BalanceSheet
     */
    public function findSheet($id)
    {
        return $this->find(self::SHEETS_TABLE, $id);
    }



    /**
     * @param  Card $data
     * @return int | false
     */
    public function insert(Card $card)
    {
        if (!$card->hasAllData()) {
            throw new EncryptedStorageException("Cannot save card without all data");
        }

        $encryptedData = $this->encrypt($card);

        echo "inserting...";

        return $this->db->insert(self::CARDS_TABLE,
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
            throw new EncryptedStorageException("Cannot update card without all data");
        }

        $encryptedCard = $this->encrypt($card);

        echo "updating...";

        return $this->db->update(self::CARDS_TABLE,
            ['data' => $encryptedCard],
            ['id' => $card->getHash()]
        );
    }

    /**
     * @return void
     * @throws EncryptedStorageException
     */
    public function inititalize()
    {
        $this->createTable(self::CARDS_TABLE);
        $this->createTable(self::SHEETS_TABLE);
    }

    private function createTable($table)
    {
        $existsSql = "SELECT name FROM sqlite_master WHERE type = 'table' AND name = '{$table}'";
        $tableExists = $this->db->query($existsSql)->fetch();

        if ($tableExists) {
            echo "{$table} table already exists\n";
            return;
        }

        $createSql = "CREATE TABLE {$table} (id CHAR PRIMARY KEY NOT NULL, data CHAR);";

        $createTable = $this->db->query($createSql);

        if ($createTable) {
            echo "{$table} table created\n";
        } else {
            throw new EncryptedStorageException("Could not create database table {$table}");
        }
    }

    /**
     * @param  string $table
     * @param  string $id
     * @return EncryptedStorable | null
     */
    private function find($table, $id)
    {
        echo "finding in {$table}...";

        $storedData = $this->db->get($table,
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
        try {
            $data = Crypto::decrypt($encryptedData, $this->getKey());
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            throw new EncryptedStorageException("Wrong key or modified/corrupted data", 0, $e);
        }

        return new Card(json_decode($data));
    }

    /**
     * @return Key
     */
    private function getKey()
    {
        return Key::loadFromAsciiSafeString($this->key);
    }

    /**
     * @return medoo
     */
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

    /**
     * @return string
     */
    private function getDbFile()
    {
        if (!is_file(self::DB_FILE)) {
            touch(self::DB_FILE);
        }

        if (!is_writable(self::DB_FILE)) {
            throw new EncryptedStorageException("Can't write to database");
        }

        return self::DB_FILE;
    }
}
