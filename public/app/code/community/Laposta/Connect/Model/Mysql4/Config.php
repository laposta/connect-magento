<?php


class Laposta_Connect_Model_Mysql4_Config extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('lapostaconnect/config', 'subscriber_id');
    }
} 
