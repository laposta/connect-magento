<?php

class Laposta_Connect_Helper_Subscribe extends Mage_Core_Helper_Abstract
{
    public function refreshSubscriberList($listId)
    {
        /** @var $customerCollection Mage_Customer_Model_Entity_Customer_Collection */
        $customerCollection = Mage::getModel('customer/customer')->getCollection();

        /** @var $subscriberCollection Laposta_Connect_Model_Mysql4_Subscriber_Collection */
        $subscriberCollection = Mage::getModel('lapostaconnect/subscriber')->getCollection();

        $subscribed = array_flip($subscriberCollection->getColumnValues('customer_id'));

        /** @var $customer Mage_Customer_Model_Entity_Customer */
        foreach ($customerCollection as $customer) {
            $customerId = $customer->getEntityId();

            if (isset($subscribed[$customerId])) {
                continue;
            }

            $subscriber = $subscriberCollection->getNewEmptyItem();
            $subscriber->setListId($listId);
            $subscriber->setCustomerId($customerId);
            $subscriber->setUpdatedTime($subscriberCollection->formatDate(time()));

            $subscriber->save();
        }
    }
}
