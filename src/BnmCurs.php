<?php

namespace Fruitware\Bnm;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;


class BnmCurs
{
    /** @var string Folder name where we save XML files */
    public $folder = 'file';

    /** @var string Path to file */
    private $source = '';


    /** @var string Date of exchange rate */
    private $date = '';


    /** @var string XML array with rates */
    private $ratesXmlArray = [];


    /** @var string BnmRate array */
    private $ratesObjectArray = [];

    /**
     * Load XML file with exchange rates by date from http://www.bnm.md/
     * @param null $date
     * @throws BnmException
     */
    public function __construct($date = null)
    {
        $cur_date = new DateTime(date('d.m.Y'));
        if (!isset($date)) {
            $this->date = $cur_date->format('d.m.Y');
        } else if ($date instanceof DateTime) {
            if($cur_date < $date) {
                throw new BnmException('Max date must be current date');
            }
            $this->date = $date->format('d.m.Y');
        } else {
            throw new BnmException('Date has an invalid format');
        }
        $this->load();
    }

    /**
     * Converts Lei the specified currency
     * @param $quantity
     * @param $currCode
     * @return float
     */
    public function exchange($quantity, $currCode) {
        $BnmRate = $this->checkCurrencyCode($currCode);

        return $BnmRate->exchange($quantity);
    }

    /**
     * Creating folder where we save XML file. Save XML currency array to object currency array
     * @param bool $reload
     * @throws BnmException
     */
    public function load($reload = false) {
        $this->folder = trim($this->folder, '/');
        $dir = dirname ( __FILE__ ).'/'.$this->folder;
        $this->source = $dir.'/'.'ratesXmlArray'.$this->date.'.xml';
        if(!is_dir($dir)) {
            mkdir($dir, '0755');
            if(!is_dir($dir)) {
                throw new BnmException('Error loading xml');
            }
        }
        if ( !file_exists($this->source) || $reload) {
            $this->saveRates($this->source, $this->date);
        }
        $this->ratesXmlArray = simplexml_load_file($this->source);
        if(!isset($this->ratesXmlArray, $this->ratesXmlArray->Valute)) {
            throw new BnmException('Error loading');
        }
        foreach ($this->ratesXmlArray->Valute as $row) {
            $BnmRate = new BnmRate($row);
            $this->ratesObjectArray[strtolower($BnmRate->getCharCode())] = $BnmRate;
        }
        $this->ratesXmlArray = [];
    }

    /**
     * Get concrete exchange rate by char code
     * @param string $currCode
     * @internal param string $currency
     * @return BnmRate
     */
    public function getRate($currCode)
    {
        return $this->checkCurrencyCode($currCode);
    }

    /**
     * Load XML file
     * @param string $date
     * @return \SimpleXMLElement
     * @throws BnmException
     */
    private function loadRates($date)
    {
        $client = new Client();
        $res = $client->get('http://www.bnm.md/ru/official_exchange_rates', [
            'query' => ['get_xml' => '1', 'date' => $date]
        ]);
        if($res->getStatusCode() == "200") {
            try {
                return $res->xml();
            } catch(BnmException $e) {
                throw new BnmException('Error loading xml');
            }
        }
        throw new BnmException('Error loading');
    }

    /**
     * Save XML data to XML File
     * @param string $filename
     * @param string $date
     * @throws BnmException
     */
    private function saveRates($filename, $date)
    {
        try {
            $ratesXmlArray = $this->loadRates($date);
            if(!$ratesXmlArray->asXML($filename));
                throw new BnmException('Error saving xml');
        } catch(BnmException $e) 
        {
            throw new BnmException('Error saving xml');            
        }
    }

    /**
     * Check if currency exist
     * @param string $currCode
     * @return BnmRate
     * @throws BnmException
     */
    private function checkCurrencyCode($currCode) 
    {
        if(!isset($this->ratesObjectArray) && count($this->ratesObjectArray)) {
            throw new BnmException('Array is empty');
        }
        $currCode = strtolower($currCode);
        if (isset($this->ratesObjectArray[$currCode])) {
            return $this->ratesObjectArray[$currCode];
        }
        throw new BnmException('Such currency does not exist');
    }

}