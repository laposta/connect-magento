<?php

/**
 * Events Observer model
 *
 * @category   Laposta
 * @package    Laposta_Connect
 */
class Laposta_Connect_Model_Observer
{
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
        $subscriber = $collection->getItemsByColumnValue('customer_id', $customer->getEntityId());

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
            $subscriber->setCustomerId($customer->getEntityId());
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
        $customer = $observer->getEvent()->getCustomer();

        if (!$customer instanceof Mage_Customer_Model_Customer) {
            return;
        }

        /** @var $collection Laposta_Connect_Model_Mysql4_Subscriber_Collection */
        $collection = Mage::getModel('lapostaconnect/subscriber')->getCollection();
        $subscriber = $collection->getItemsByColumnValue('customer_id', $customer->getEntityId());

        if (!$subscriber instanceof Laposta_Connect_Model_Subscriber || $subscriber->isEmpty()) {
            return;
        }

        $subscriber->setCustomerId('');
        $subscriber->setUpdatedTime($collection->formatDate(time()));
        $subscriber->save();
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

        $lists->save();

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
}
