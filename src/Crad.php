<?php

use Crad\CardReader;
use Crad\Exception;
use Seld\CliPrompt\CliPrompt;

class Crad
{

    private $reader;

    public function __construct()
    {
        $this->reader = new CardReader();
    }

    public function run()
    {
        while ($line = CliPrompt::hiddenPrompt()) {
            $this->parseInput($line);
        }

        echo "all done\n";
    }

    private function parseInput($input)
    {
        try {
            $this->reader->read($input);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }
}