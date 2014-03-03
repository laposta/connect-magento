<?php

class Laposta_Connect_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Utility to check if admin is logged in
     *
     * @return bool
     */
    public function isAdmin()
    {
        return Mage::getSingleton('admin/session')->isLoggedIn();
    }

    /**
     * Check if Magento is EE
     *
     * @return bool
     */
    public function isEnterprise()
    {
        return is_object(Mage::getConfig()->getNode('global/models/enterprise_enterprise'));
    }

    /**
     * Check if Laposta plugin is enabled
     *
     * @return $this
     */
    public function isLapostaEnabled()
    {
        return $this->config('enable_log') === '1';
    }

    /**
     * Get module configuration value
     *
     * @param string $value
     * @param string $store
     *
     * @return mixed Configuration setting
     */
    public function config($value, $store = null)
    {
        $store = is_null($store) ? Mage::app()->getStore() : $store;

        $configscope = Mage::app()->getRequest()->getParam('store');
        if ($configscope && ($configscope !== 'undefined')) {
            $store = $configscope;
        }

        return Mage::getStoreConfig("lapostaconnect/laposta/$value", $store);
    }

    /**
     * Logging facility
     *
     * @param mixed  $data     Message to save to file
     * @param string $filename log filename, default is <Laposta.log>
     *
     * @return Mage_Core_Model_Log_Adapter
     */
    public function log($data, $filename = 'Laposta.log')
    {
        if ($this->config('enable_log') === '1') {
            if ($data instanceof Exception) {
                $data    = array(
                    'exception' => get_class($data),
                    'message'   => $data->getMessage(),
                    'line'      => $data->getLine(),
                    'file'      => $data->getFile(),
                );
            }

            return Mage::getModel('core/log_adapter', $filename)->log($data);
        }
    }
} 
