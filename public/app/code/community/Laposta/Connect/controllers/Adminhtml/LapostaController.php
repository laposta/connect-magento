<?php

class Laposta_Connect_Adminhtml_LapostaController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Initialize action
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _initAction()
    {
        $this->_title($this->__('Newsletter'))
            ->_title($this->__('Laposta'));

        $this->loadLayout();
        $this->_setActiveMenu('newsletter/lapostaconnect');

        return $this;
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
