<?php

namespace Fruitware\Bnm;

use DateTime;
use GuzzleHttp\Client;
use Fruitware\Bnm\Exception\BnmException;

class Curs
{
	/**
	 * @var DateTime Date of exchange rate
	 */
	private $_date;

	/**
	 * @var string Folder name where we save XML files
	 */
	private $_folder = 'files';

	/**
	 * @var string
	 */
	private $_lang;

	/**
	 * @var string Rate array
	 */
    private $_ratesObjectArray = [];

	/**
	 * Load XML file with exchange rates by date from http://www.bnm.md/
	 *
	 * @param DateTime $date
	 * @param string   $lang
	 *
	 * @throws Exception\BnmException
	 */
    public function __construct(DateTime $date = null, $lang = 'ru')
    {
	    $this->_lang = $lang;

        $currDate = new DateTime();
        if (!isset($date)) {
            $this->_date = $currDate;
        } else if ($date instanceof DateTime) {
            if($currDate < $date) {
                throw new BnmException('Max date must be current date');
            }
            $this->_date = $date;
        } else {
            throw new BnmException('Date has an invalid format');
        }
        $this->load();
    }

    /**
     * Converts one currency to another withing current rate
     *
     * @param $currencyFromCode
     * @param $quantity
     * @param NULL $currencyToCode
     *
     * @return float
     */
    public function exchange($currencyFromCode, $quantity, $currencyToCode = NULL)
    {
        $fromQuantity = $currencyFromCode == 'MDL' ? $quantity : $this->getRate($currencyFromCode)->exchangeFrom($quantity);
        if (empty($currencyToCode) || $currencyToCode == 'MDL')
        {
            return $fromQuantity;
        }
        return $this->getRate($currencyToCode)->exchangeTo($fromQuantity);
    }

    /**
     * Creating folder where we save XML file. Save XML currency array to object currency array
     *
     * @param bool $reload
     *
     * @throws BnmException
     */
    public function load($reload = false)
    {
        $this->_folder = trim($this->_folder, '/');
        $dir = dirname( __FILE__ ).'/'.$this->_folder;
        $source = $dir.'/'.$this->_date->format('Y-m-d').'.xml';
        if(!is_dir($dir)) {
            if(!mkdir($dir, 0755)) {
                throw new BnmException('Cant create directory for files');
            }
        }

        if (!file_exists($source) || $reload) {
            $xml = $this->saveRates($source, $this->_date);
        } else {
	        $xml = simplexml_load_file($source);
        }

        if(!isset($xml, $xml->Valute)) {
            throw new BnmException('Error loading');
        }

        foreach ($xml->Valute as $row) {
            $bnmRate = new Rate($row);
            $this->_ratesObjectArray[strtolower($bnmRate->getCharCode())] = $bnmRate;
        }
    }

	/**
	 * Get concrete exchange rate by char code
	 *
	 * @param string $currCode
	 *
	 * @return Rate
	 * @throws BnmException
	 */
    public function getRate($currCode)
    {
	    $currCode = strtolower($currCode);
	    if (isset($this->_ratesObjectArray[$currCode])) {
		    return $this->_ratesObjectArray[$currCode];
	    }

	    throw new BnmException('Such currency does not exist');
    }

    /**
     * Load XML file
     *
     * @param DateTime $date
     *
     * @return \SimpleXMLElement
     * @throws BnmException
     */
    private function loadRates(DateTime $date)
    {
        $client = new Client();
	    /**
	     * @var \GuzzleHttp\Message\Response $result
	     */
	    $result = $client->get('http://www.bnm.md/'.$this->_lang.'/official_exchange_rates', [
            'query' => ['get_xml' => '1', 'date' => $date->format('d.m.Y')]
        ]);
        if($result->getStatusCode() == "200") {
            try {
                return $result->xml();
            } catch(BnmException $e) {
                throw new BnmException('Error loading xml');
            }
        }
        throw new BnmException('Error loading');
    }

	/**
	 * Save XML data to XML File
	 *
	 * @param string   $filename
	 * @param DateTime $date
	 *
	 * @return \SimpleXMLElement
	 * @throws BnmException
	 */
    private function saveRates($filename, DateTime $date)
    {
        $ratesXmlArray = $this->loadRates($date);
        if($ratesXmlArray->asXML($filename)){
            return $ratesXmlArray;
        }

        throw new BnmException('Error saving xml');
    }
}