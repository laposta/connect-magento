<?php

/**
 * Events Observer model
 *
 * @category   Laposta
 * @package    Laposta_Connect
 */
class Laposta_Connect_Model_Observer
{
    private $currentApiKey = '';

    /**
     * Handle subscribe event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function handleCustomerSave(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if (!$customer instanceof Mage_Customer_Model_Customer) {
            return;
        }

        /** @var $collection Laposta_Connect_Model_Mysql4_Subscriber_Collection */
        $collection = Mage::getModel('lapostaconnect/subscriber')->getCollection();
        $subscriber = $collection->getItemByColumnValue('customer_id', $customer->getId());

        if (!$subscriber instanceof Laposta_Connect_Model_Subscriber || $subscriber->isEmpty()) {
            /*
             * Nothing to do.
             */

            return;
        }

        if ($subscriber->getData('customer_id') != '') {
            $subscriber->setUpdatedTime($collection->formatDate(time()));
            $subscriber->save();
        }
    }

    /**
     * Handle subscribe event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function handleCustomerDelete(Varien_Event_Observer $observer)
    {
        /*
         * method is now obsolete.
         *
         * Subscriber removal is handle through newsletter_subscriber_delete_after event.
         */
    }

    /**
     * Handle subscribe event
     *
     * @param Varien_Event_Observer $observer
     */
    public function handleNewsletterSubscriberSave(Varien_Event_Observer $observer)
    {
        /** @var $nativeSubscriber Mage_Newsletter_Model_Subscriber */
        $nativeSubscriber = $observer->getEvent()->getSubscriber();

        if (!$nativeSubscriber instanceof Mage_Newsletter_Model_Subscriber) {
            return;
        }

        /** @var $collection Laposta_Connect_Model_Mysql4_Subscriber_Collection */
        $collection = Mage::getModel('lapostaconnect/subscriber')->getCollection();
        $customerId = $nativeSubscriber->getCustomerId();

        /*
         * find laposta subscriber by subscriber id
         */

        /** @var $subscriber Laposta_Connect_Model_Subscriber */
        $subscriber = $collection->getItemByColumnValue('newsletter_subscriber_id', $nativeSubscriber->getId());

        /*
         * if not found and customer id is not '0' it could be a legacy record
         */

        if ($customerId != "0" && (!$subscriber instanceof Laposta_Connect_Model_Subscriber || $subscriber->isEmpty())) {
            $subscriber = $collection->getItemByColumnValue('customer_id', $customerId);

            /*
             * update entries stored without a newsletter_subscriber_id
             */

            $subscriber->setNewsletterSubscriberId($nativeSubscriber->getId());
            $subscriber->save();
        }

        /*
         * if subscriber still doesn't exist then it really doesn't exist yet.
         */

        if (!$subscriber instanceof Laposta_Connect_Model_Subscriber || $subscriber->isEmpty()) {
            /** @var $lists Laposta_Connect_Model_Mysql4_List_Collection */
            $lists = Mage::getModel('lapostaconnect/list')->getCollection();
            /** @var $list Laposta_Connect_Model_List */
            $list = $lists->setOrder('list_id')->getFirstItem();

            if (!$list instanceof Laposta_Connect_Model_List) {
                return;
            }

            $subscriber = $collection->getNewEmptyItem();
            $subscriber->setListId($list->getListId());
            $subscriber->setCustomerId($customerId);
            $subscriber->setNewsletterSubscriberId($nativeSubscriber->getId());
            $subscriber->setUpdatedTime($collection->formatDate(time()));
            $subscriber->save();
        }
    }

    /**
     * Handle subscriber delete event
     *
     * @param Varien_Event_Observer $observer
     */
    public function handleNewsletterSubscriberDelete(Varien_Event_Observer $observer)
    {
        /** @var $nativeSubscriber Mage_Newsletter_Model_Subscriber */
        $nativeSubscriber = $observer->getEvent()->getSubscriber();

        if (!$nativeSubscriber instanceof Mage_Newsletter_Model_Subscriber) {
            return;
        }


        /** @var $collection Laposta_Connect_Model_Mysql4_Subscriber_Collection */
        $collection = Mage::getModel('lapostaconnect/subscriber')->getCollection();
        $customerId = $nativeSubscriber->getCustomerId();

        /*
         * find laposta subscriber by subscriber id
         */

        /** @var $subscriber Laposta_Connect_Model_Subscriber */
        $subscriber = $collection->getItemByColumnValue('newsletter_subscriber_id', $nativeSubscriber->getId());

        /*
         * if not found and customer id is not '0' it could be a legacy record
         */

        if ($customerId != "0" && (!$subscriber instanceof Laposta_Connect_Model_Subscriber || $subscriber->isEmpty())) {
            $subscriber = $collection->getItemByColumnValue('customer_id', $customerId);
        }

        /*
         * if subscriber still doesn't exist then it really doesn't exist yet.
         */

        if (!$subscriber instanceof Laposta_Connect_Model_Subscriber || $subscriber->isEmpty()) {
            return;
        }

        $subscriber->setCustomerId('');
        $subscriber->setNewsletterSubscriberId('');
        $subscriber->setUpdatedTime($collection->formatDate(time()));
        $subscriber->save();
    }

    /**
     * Handle config initialization event.
     *
     * @param Varien_Event_Observer $observer
     */
    public function handleInitConfig(Varien_Event_Observer $observer)
    {
        /*
         * Record the current api key to detect changes
         */
        $this->currentApiKey = Mage::helper('lapostaconnect')->config('api_key');
    }

    /**
     * Handle subscribe event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function handleSaveConfig(Varien_Event_Observer $observer)
    {
        if (Mage::helper('lapostaconnect')->config('api_key') !== $this->currentApiKey) {
            Mage::helper('lapostaconnect')->log('Laposta API key changed. Resetting all records.');

             /*
              * clear the laposta token and sync times
              */
            $this->resetSynchronised();
        }

        $list = $this->handleSaveListConfig();

        if (!$list instanceof Laposta_Connect_Model_List) {
            return;
        }

        $this->handleSaveFieldsConfig($list);
    }

    /**
     * Store and sync the list configuration
     *
     * @return Laposta_Connect_Model_List|null
     */
    protected function handleSaveListConfig()
    {
        $listName           = Mage::helper('lapostaconnect')->config('list_name');
        $subscribeCustomers = false;

        /** @var $lists Laposta_Connect_Model_Mysql4_List_Collection */
        $lists = Mage::getModel('lapostaconnect/list')->getCollection();
        /** @var $list Laposta_Connect_Model_List */
        $list = $lists->setOrder('list_id')->getFirstItem();

        if (!$list instanceof Laposta_Connect_Model_List) {
            $list = $lists->getNewEmptyItem();
            $subscribeCustomers = true;

            $lists->addItem($list);
        }

        $list->setListName($listName);
        $list->setUpdatedTime($lists->formatDate(time()));

        /*
         * Save here to ensure list_id is generated for new list entries
         */
        $list->save();

        try {
            /** @var $syncHelper Laposta_Connect_Helper_Sync */
            $syncHelper = Mage::helper('lapostaconnect/sync');
            $syncHelper->syncList($list);
        }
        catch (Exception $e) {
            Mage::helper('lapostaconnect')->log($e);
        }

        $list->save();

        if ($subscribeCustomers) {
            Mage::helper('lapostaconnect/subscribe')->refreshSubscriberList($list->getListId());
        }

        return $list;
    }

    /**
     * Store and sync field mappings
     *
     * @param Laposta_Connect_Model_List $list
     *
     * @return void
     */
    protected function handleSaveFieldsConfig(
        Laposta_Connect_Model_List $list
    ) {
        $fieldsMap = $this->resolveFieldsMap();
        $added     = array();
        $updated   = array();
        $removed   = array();
        $skipped   = array();
        /** @var $fields Laposta_Connect_Model_Mysql4_Field_Collection */
        $fields = Mage::getModel('lapostaconnect/field')->getCollection();

        $fields->addFilter('list_id', $list->getListId());

        /** @var $field Laposta_Connect_Model_Field */
        foreach ($fields as $key => $field) {
            $fieldName = $field->getFieldName();

            if (!isset($fieldsMap[$fieldName])) {
                $removed[$fieldName] = $field;
                $fields->removeItemByKey($key);
                $field->delete();

                continue;
            }

            $field->setFieldRelation($fieldsMap[$fieldName]);
            $field->setUpdatedTime($fields->formatDate(time()));
            $updated[$fieldName] = $field;
        }

        $fieldsMap = array_diff_key($fieldsMap, $updated, $removed, $skipped);

        /**
         * Add the remaining entries in fieldsMap
         */
        foreach ($fieldsMap as $fieldName => $fieldRelation) {
            $field = $fields->getNewEmptyItem();
            $field->setListId($list->getListId());
            $field->setFieldName($fieldName);
            $field->setFieldRelation($fieldRelation);
            $field->setUpdatedTime($fields->formatDate(time()));

            $fields->addItem($field);
            $added[$fieldName] = $field;
        }

        try {
            /** @var $syncHelper Laposta_Connect_Helper_Sync */
            $syncHelper = Mage::helper('lapostaconnect/sync');
            $syncHelper->syncFields($list, $fields);
        }
        catch (Exception $e) {
            Mage::helper('lapostaconnect')->log($e);
        }

        $fields->save();
    }

    /**
     * Resolve the fields map
     *
     * @return array
     */
    protected function resolveFieldsMap()
    {
        $list = unserialize(Mage::helper('lapostaconnect')->config('map_fields'));

        if ($list === false) {
            return array();
        }

        $result = array();

        foreach ($list as $mapping) {
            $result[$mapping['magento']] = $mapping['laposta'];
        }

        return $result;
    }

    /**
     * Reset tokens and sync times of all lists, fields, and subscribers.
     */
    protected function resetSynchronised()
    {
        /*
         * Lists
         */

        /** @var $lists Laposta_Connect_Model_Mysql4_List_Collection */
        $lists = Mage::getModel('lapostaconnect/list')->getCollection();

        /** @var $list Laposta_Connect_Model_List */
        foreach ($lists as $list) {
            $list->setLapostaId('');
            $list->setWebhookToken('');
            $list->setSyncTime(null);
        }

        $lists->save();

        Mage::helper('lapostaconnect')->log('Lists reset OK.');

        /*
         * Fields
         */

        $fields = Mage::getModel('lapostaconnect/field')->getCollection();

        /** @var $field Laposta_Connect_Model_Field */
        foreach ($fields as $field) {
            $field->setLapostaId('');
            $field->setLapostaTag('');
            $field->setSyncTime(null);
        }

        $fields->save();

        Mage::helper('lapostaconnect')->log('Fields reset OK.');

        /*
         * Subscribers
         */

        /** @var $subscribers Laposta_Connect_Model_Mysql4_Subscriber_Collection */
        $subscribers = Mage::getModel('lapostaconnect/subscriber')->getCollection();

        /** @var $subscriber Laposta_Connect_Model_Subscriber */
        foreach ($subscribers as $subscriber) {
            $subscriber->setLapostaId('');
            $subscriber->setSyncTime(null);
        }

        $subscribers->save();

        Mage::helper('lapostaconnect')->log('Subscribers reset OK.');
    }
}
