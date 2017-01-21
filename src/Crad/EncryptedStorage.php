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

    const CARDS_TABLE = 'cards';
    const SHEETS_TABLE = 'sheets';

    /**
     * @param Config $config
     */
    public function __construct(Config $config = null)
    {
        if (!$config) {
            $config = new Config();
        }

        $this->key = file_get_contents($config->keyfile);
        $this->db = $this->getDb($config->dbfile);
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
    public function findBalanceSheet($id)
    {
        return $this->find(self::SHEETS_TABLE, $id);
    }

    /**
     * @param  EncryptedStorable $data
     * @return int | false
     */
    public function insert($data)
    {
        if (!$data->hasAllData()) {
            throw new EncryptedStorageException("Cannot save without all data");
        }

        $encryptedData = $this->encrypt($data);

        echo "inserting...\n";

        return $this->db->insert($this->determineTable($data),
            ['id' => $data->getHash(), 'data' => $encryptedData]
        );
    }

    /**
     * @param  EncryptedStorable $data
     * @return int | false
     */
    public function update($data)
    {
        if (!$data->hasAllData()) {
            throw new EncryptedStorageException("Cannot update without all data");
        }

        $encryptedData = $this->encrypt($data);

        echo "updating...\n";

        return $this->db->update($this->determineTable($data),
            ['data' => $encryptedData],
            ['id' => $data->getHash()]
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

    /**
     * @return int | false
     */
    public function countCards()
    {
        return $this->db->count(self::CARDS_TABLE);
    }

    /**
     * @return int | false
     */
    public function countBalanceSheets()
    {
        return $this->db->count(self::SHEETS_TABLE);
    }

    /**
     * @return array | false
     */
    public function getCardIds()
    {
        return $this->db->select(self::CARDS_TABLE, 'id');
    }

    /**
     * @return array | false
     */
    public function getBalanceSheetIds()
    {
        return $this->db->select(self::SHEETS_TABLE, 'id');
    }

    /**
     * @param string $table
     * @throws EncryptedStorageException
     */
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
        #echo "finding in {$table}...";

        $storedData = $this->db->get($table,
            ['id', 'data'],
            ['id' => $id]
        );

        if ($storedData) {
            return $this->decrypt($storedData['data'], $table);
        }

        return null;
    }

    /**
     * @param  EncryptedStorable $class
     * @return string
     */
    private function determineTable($class)
    {
        if ($class instanceof Card) {
            return self::CARDS_TABLE;
        }

        if ($class instanceof BalanceSheet) {
            return self::SHEETS_TABLE;
        }

        throw new EncryptedStorageException("Tried to store unknown data type");
    }

    /**
     * @param  EncryptedStorable $data
     * @return string
     */
    private function encrypt($data)
    {
        return Crypto::encrypt(json_encode($data), $this->getKey());
    }

    /**
     * @param  string $encryptedData
     * @param  string $table
     * @return EncryptedStorable
     */
    private function decrypt($encryptedData, $table)
    {
        try {
            $data = Crypto::decrypt($encryptedData, $this->getKey());
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            throw new EncryptedStorageException("Wrong key or modified/corrupted data", 0, $e);
        }

        return $this->determineClass($table)->hydrate(json_decode($data));
    }

    private function determineClass($table)
    {
        if ($table == self::CARDS_TABLE) {
            return new Card();
        }

        if ($table == self::SHEETS_TABLE) {
            return new BalanceSheet();
        }

        throw new EncryptedStorageException("Unknown table");
    }

    /**
     * @return Key
     */
    private function getKey()
    {
        return Key::loadFromAsciiSafeString($this->key);
    }

    /**
     * @param string $dbFile path to sqlite database file
     * @return medoo
     */
    private function getDb($dbFile)
    {
        if ($this->db) {
            return $this->db;
        }

        return new medoo([
            'database_type' => 'sqlite',
            'database_file' => $this->getDbFile($dbFile)
        ]);
    }

    /**
     * @param string $dbFile
     * @return string
     */
    private function getDbFile($dbFile)
    {
        if (!is_file($dbFile)) {
            touch($dbFile);
        }

        if (!is_writable($dbFile)) {
            throw new EncryptedStorageException("Can't write to database");
        }

        return $dbFile;
    }
}
