<?php

/**
 * Laposta webhooks controller
 *
 * @category   Laposta
 * @package    Laposta_Connect
 */
class Laposta_Connect_SubscribeAllController extends Mage_Core_Controller_Front_Action
{

    /**
     * Entry point for all webhook operations
     */
    public function indexAction()
    {
//        Mage::helper('lapostaconnect/subscribe')->refreshSubscriberList(3);

        Laposta_Connect_Model_Cron::export();

        die(__CLASS__);
    }

}
