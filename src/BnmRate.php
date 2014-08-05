<?php

namespace Fruitware\Bnm;

/**
 * Concrete exchange object
 * Class BnmRate
 * @package Fruitware\Bnm
 */
class BnmRate {

    private $_id;
    private $_num_code;
    private $_char_code;
    private $_nominal;
    private $_name;
    private $_value;

    public function __construct($node) {
        $this->_id = $node["ID"];
        $this->_num_code = $node->NumCode;
        $this->_char_code = $node->CharCode;
        $this->_nominal = $node->Nominal;
        $this->_name = $node->Name;
        $this->_value = $node->Value;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getNumCode()
    {
        return $this->_num_code;
    }

    public function getCharCode()
    {
        return $this->_char_code;
    }

    public function getNominal()
    {
        return $this->_nominal;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }

    public function setNumCode($num_code)
    {
        $this->_num_code = $num_code;
    }

    public function setCharCode($char_code)
    {
        $this->_char_code = $char_code;
    }

    public function setNominal($nominal)
    {
        $this->_nominal = $nominal;
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * @param $quantity string
     * @return float
     */
    public function exchange($quantity)
    {
        $rate 		=	(double) $this->_value;
        $nominal 	= 	(double) $this->_nominal;

        $result = (double) ( $quantity / $nominal / $rate );

        return $result;
    }

}