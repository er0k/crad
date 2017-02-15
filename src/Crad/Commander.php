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
     * @return Crad
     */
    public function execute($cmd)
    {
         switch ($cmd) {
            case 'help':
            case 'h':
                $this->showHelp();
                break;
            case 'quit':
            case 'q':
                die("Bye\n");
            case 'total':
            case 't':
                $this->calculateTotal();
                break;
            case 'new':
            case 'n':
                $this->crad->initialize(true);
                break;
            case 'count':
            case 'c':
                $this->analyzer->countCardsAndSheets();
                break;
            case 'show':
            case 's':
                $this->crad->getCard()->showInfo();
                $this->crad->getBalanceSheet()->showInfo();
                break;;
            case 'balance':
            case 'b':
                $this->analyzer->showBalances();
                break;
            case 'refresh':
            case 'r':
                $this->analyzer->refreshBalances();
                break;
            case 'find':
            case 'f':
                $this->find();
                break;
            case 'l':
                system('clear');
                break;
            case 'break':
                // this command will get returned from the reader if it has read
                // a card track or a CVV. it's only here to help break out of the
                // main loop of parsing input, and allow card data to get pushed
                // into the program without having to hit Enter each time
                break;;
            default:
                echo "$cmd command not yet implemented\n";
                break;;
        }

        return $this->crad;
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