<?php


class Laposta_Connect_Model_Mysql4_Config_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        //parent::_construct():
        $this->init('lapostaconnect/config');
    }
} 
