<?php

class Laposta_Connect_Helper_Subscribe extends Mage_Core_Helper_Abstract
{
    public function refreshSubscriberList($listId)
    {
        /** @var $subscriberCollection Mage_Newsletter_Model_Resource_Subscriber_Collection */
        $nativeSubscriberCollection = Mage::getModel('newsletter/subscriber')->getCollection();

        /** @var $subscriberCollection Laposta_Connect_Model_Mysql4_Subscriber_Collection */
        $subscriberCollection = Mage::getModel('lapostaconnect/subscriber')->getCollection();

        $subscriberIdList = array_flip($subscriberCollection->getColumnValues('newsletter_subscriber_id'));
        $customerIdList   = array_flip($subscriberCollection->getColumnValues('customer_id'));

        /** @var $nativeSubscriber Mage_Newsletter_Model_Subscriber */
        foreach ($nativeSubscriberCollection as $nativeSubscriber) {
            $customerId         = $nativeSubscriber->getCustomerId();
            $nativeSubscriberId = $nativeSubscriber->getId();

            if (isset($customerIdList[$customerId]) || isset($subscriberIdList[$nativeSubscriberId])) {
                continue;
            }

            $subscriber = $subscriberCollection->getNewEmptyItem();
            $subscriber->setListId($listId);
            $subscriber->setCustomerId($customerId);
            $subscriber->setNewsletterSubscriberId($nativeSubscriberId);
            $subscriber->setUpdatedTime(date('Y-m-d H:i:s'));
            $subscriber->save();
        }
    }
}
