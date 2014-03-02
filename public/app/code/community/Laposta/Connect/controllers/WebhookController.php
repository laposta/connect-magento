<?php

/**
 * Laposta webhooks controller
 *
 * @category   Laposta
 * @package    Laposta_Connect
 */
class Laposta_Connect_WebhookController extends Mage_Core_Controller_Front_Action
{

    /**
     * Entry point for all webhook operations
     */
    public function indexAction()
    {
        $subscriber = $subscriber = Mage::getModel('newsletter/subscriber')
            ->loadByEmail('merten@codeblanche.com');

        var_dump($subscriber);

        die(__CLASS__);
    }

}
