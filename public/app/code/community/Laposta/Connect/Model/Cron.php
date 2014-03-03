<?php


class Laposta_Connect_Model_Cron
{
    /**
     *
     */
    public static function export()
    {
        /** @var $subscribers Laposta_Connect_Model_Mysql4_Subscriber_Collection */
        $subscribers = Mage::getModel('lapostaconnect/subscriber')->getCollection();
        $subscribers->getSelect()->where('`updated_time` > `sync_time`')->orWhere('`sync_time` IS NULL');

        Mage::helper('lapostaconnect/sync')->syncSubscribers($subscribers);
    }
} 
