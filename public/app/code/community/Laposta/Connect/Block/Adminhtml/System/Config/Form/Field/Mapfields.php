<?php

/**
 * System configuration form field renderer for mapping MergeVars fields with Magento
 * attributes.
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_MageMonkey
 * @author     Ebizmarts Team <info@ebizmarts.com>
 */
class Laposta_Connect_Block_Adminhtml_System_Config_Form_Field_Mapfields
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    public function __construct()
    {
        $this->addColumn(
            'magento',
            array(
                'label' => Mage::helper('lapostaconnect')->__('Customer'),
                'style' => 'width:120px',
            )
        );

        $this->addColumn(
            'laposta',
            array(
                'label' => Mage::helper('lapostaconnect')->__('Laposta'),
                'style' => 'width:120px',
            )
        );

        $this->_addAfter       = false;
        $this->_addButtonLabel = Mage::helper('lapostaconnect')->__('Add field');
        parent::__construct();
    }
}
