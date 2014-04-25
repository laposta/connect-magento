<?php

class Laposta_Connect_Model_Subscriber extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();

        $this->_init('lapostaconnect/subscriber');
    }
}
