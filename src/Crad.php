<?php

use Crad\Reader;
use Crad\Exception;
use Seld\CliPrompt\CliPrompt;

class Crad
{
    /** @var Reader */
    private $reader;

    public function __construct()
    {
        $this->reader = new Reader();
    }

    public function run()
    {
        while ($line = CliPrompt::hiddenPrompt()) {
            $this->parseInput($line);
        }
    }

    private function parseInput($input)
    {
        ob_start();

        try {
            $this->reader->read($input);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }
}