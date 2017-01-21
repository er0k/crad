<?php

namespace Crad;

use Crad;

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
        echo "commands:\n";
        echo "!help\tshow this message\n";
        echo "!quit\texit\n";
        echo "!total\ttotal up all balances\n";
        echo "!count\tcount all the cards and balance sheets\n";
        echo "!show\tshow info of current card and balance sheet\n";
        echo "!balance\tshow all balances of all cards\n";
        echo "\n";
    }

    private function calculateTotal()
    {
        echo 'calculating total...';

        $total = $this->analyzer->getTotal();

        echo money_format('$%i', $total) . "\n";
    }

}