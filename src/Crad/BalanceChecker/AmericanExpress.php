<?php

namespace Crad\BalanceChecker;

use Crad\Card;
use Crad\BalanceCheckerException;
use Goutte\Client;

class AmericanExpress extends AbstractChecker
{
    const URL = 'https://www.americanexpress.com/mygiftcard';

    /**
     * @return Symfony\Component\DomCrawler\Crawler
     * @throws BalanceCheckerException
     */
    protected function getDom()
    {
        if ($this->dom) {
            return $this->dom;
        }

        $client = new Client();

        if (!empty($this->ua)) {
            $client->setHeader('user-agent', $this->ua);
        }

        $dom = $client->request('GET', self::URL);
        $form = $dom->selectButton('Continue')->form();

        $data = [
            '_token' => $form->get('_token')->getValue(),
            'errorFlag' => $form->get('errorFlag')->getValue(),
            'backButton' => $form->get('backButton')->getValue(),
            'timeStamp' => (new \DateTime())->format('Y-n-j G:i:s'),
            'timeZoneOffset' => '-5',
            'buttonPressed' => $form->get('buttonPressed')->getValue(),
            'cardDetailsVO.cardNumber' => $this->getNumberWithDashes(),
        ];

        $url = 'https://www279.americanexpress.com/GPTHBIWeb/fetchCSCDetails.do?clientkey=retail%20sales%20channel';

        $dom = $client->request('POST', $url, $data);
        $form = $dom->selectButton('Continue')->form();

        $data = [
            '_token' => $form->get('_token')->getValue(),
            'errorFlag' => $form->get('errorFlag')->getValue(),
            'backButton' => $form->get('backButton')->getValue(),
            'timeStamp' => $form->get('timeStamp')->getValue(),
            'timeZoneOffset' => $form->get('timeZoneOffset')->getValue(),
            'buttonPressed' => $form->get('buttonPressed')->getValue(),
            'cardDetailsVO.cscNumber' => $this->card->getCvv(),
        ];

        $url = 'https://www279.americanexpress.com/GPTHBIWeb/CSCValidations.do?clientkey=retail%20sales%20channel';

        $dom = $client->request('POST', $url, $data);

        // #content > div.container > div.wrapper > div.row.section-status > div.col-sm-4.balance > dl > dt
        if (!$dom->filter('.balance')->count()) {
            throw new BalanceCheckerException("Wrong credentials");
        }

        return $dom;
    }

    /**
     * @return float
     * @throws BalanceCheckerException
     */
    protected function getBalance()
    {
        if (!$this->dom) {
            throw new BalanceCheckerException("No DOM");
        }

        $domBalance = $this->dom->filter('.balance')->text();

        return $this->cleanAmount($domBalance);
    }

    /**
     * @return array
     * @throws BalanceCheckerException
     */
    protected function getTransactions()
    {
        if (!$this->dom) {
            throw new BalanceCheckerException("No DOM");
        }

        $transactions = [];

        return $transactions;
    }

    /**
     * @return string
     */
    private function getNumberWithDashes()
    {
        $number = $this->card->getNumber();

        $number = substr_replace($number, '-', 4, 0);
        $number = substr_replace($number, '-', 11, 0);

        return $number;
    }
}