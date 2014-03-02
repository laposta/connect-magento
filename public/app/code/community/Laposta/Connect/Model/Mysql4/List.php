<?php


class Laposta_Connect_Model_Mysql4_List extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('lapostaconnect/list', 'list_id');
    }
} 
