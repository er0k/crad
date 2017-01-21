<?php

namespace Crad;

use Crad;
use Seld\CliPrompt\CliPrompt;


class Commander
{
    /** @var Crad */
    private $crad;

    /** @var Analyzer */
    private $analyzer;

    /**
     * @param Crad $crad
     */
    public function __construct(Crad $crad)
    {
        $this->crad = $crad;
        $this->analyzer = new Analyzer($crad->getStorage());
    }

    /**
     * @param  string $cmd
     * @return void
     */
    public function execute($cmd)
    {
         switch ($cmd) {
            case 'help':
            case 'h':
                return $this->showHelp();
            case 'quit':
            case 'q':
                die("Bye\n");
            case 'total':
            case 't':
                return $this->calculateTotal();
            case 'new':
            case 'n':
                return $this->crad->initialize(true);
            case 'count':
            case 'c':
                return $this->analyzer->countCardsAndSheets();
            case 'show':
            case 's':
                $this->crad->getCard()->showInfo();
                $this->crad->getBalanceSheet()->showInfo();
                return;
            case 'balance':
            case 'b':
                return $this->analyzer->showBalances();
            case 'refresh':
            case 'r':
                return $this->analyzer->refreshBalances();
            case 'find':
            case 'f':
                return $this->find();
            case 'l':
                return system('clear');
            case 'break':
                // this command will get returned from the reader if it has read
                // a card track or a CVV. it's only here to help break out of the
                // main loop of parsing input, and allow card data to get pushed
                // into the program without having to hit Enter each time
                return;
            default:
                echo "$cmd command not yet implemented\n";
                return;
        }
    }

    private function showHelp()
    {
        echo "commands:\n\n";
        echo "!help\t\tshow this message\n";
        echo "!total\t\ttotal up all balances\n";
        echo "!count\t\tcount all the cards and balance sheets\n";
        echo "!show\t\tshow info of current card and balance sheet\n";
        echo "!new\t\tclear current card and balance sheet from memory\n";
        echo "!balance\tshow all balances of all cards\n";
        echo "!refresh\trefresh balances of all cards\n";
        echo "!find\t\tsearch for a card by number\n";
        echo "!quit\t\texit\n";
        echo "!l\t\tclear\n";

        echo "\n";
    }

    private function calculateTotal()
    {
        echo 'calculating total...';

        $total = $this->analyzer->getTotal();

        echo money_format('$%i', $total) . "\n";
    }

    private function find()
    {
        echo "search for card number: ";
        $searchFor = CliPrompt::prompt();

        /** @var Card | null */
        $result = $this->analyzer->search($searchFor);

        if ($result) {
            echo "found card\n";
            $result->showInfo();
            $this->crad->setCard($result);
        }
    }

}