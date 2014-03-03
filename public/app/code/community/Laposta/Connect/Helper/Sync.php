<?php

class Laposta_Connect_Helper_Sync extends Mage_Core_Helper_Abstract
{
    /**
     * @var array array(
     *     'type'       => Laposta_Connect_Helper_Laposta::FIELD_TYPE_TEXT,
     *     'options'    => array(),
     *     'default'    => '',
     *     'required'   => false,
     *     'showInForm' => true,
     *     'showInList' => true,
     * )
     */
    protected $fieldConfigMap = array(
        'dob'                 => array(
            'type' => Laposta_Connect_Helper_Laposta::FIELD_TYPE_DATE,
        ),
        'gender'              => array(
            'type'    => Laposta_Connect_Helper_Laposta::FIELD_TYPE_SELECT_SINGLE,
            'options' => array('', 'male', 'female'),
        ),
        'store_id'            => array(
            'type' => Laposta_Connect_Helper_Laposta::FIELD_TYPE_NUMERIC,
        ),
        'website_id'          => array(
            'type' => Laposta_Connect_Helper_Laposta::FIELD_TYPE_NUMERIC,
        ),
        'date_of_purchase'    => array(
            'type' => Laposta_Connect_Helper_Laposta::FIELD_TYPE_DATE,
        ),
        'ee_customer_balance' => array(
            'type' => Laposta_Connect_Helper_Laposta::FIELD_TYPE_NUMERIC,
        ),
        'group_id'            => array(
            'type' => Laposta_Connect_Helper_Laposta::FIELD_TYPE_NUMERIC,
        ),
    );

    /**
     * @return string
     */
    protected function resolveApiKey()
    {
        return Mage::helper('lapostaconnect')->config('api_key');
    }

    /**
     * Sync the list configuration with Laposta
     *
     * @param Laposta_Connect_Model_List $list
     *
     * @return $this
     */
    public function syncList(Laposta_Connect_Model_List $list)
    {
        if (Mage::helper('lapostaconnect')->config('active') !== 1) {
            return $this;
        }

        if (strtotime($list->getUpdatedTime()) <= strtotime($list->getSyncTime())) {
            return $this;
        }

        /** @var $laposta Laposta_Connect_Helper_Laposta */
        $laposta = Mage::helper('lapostaconnect/laposta');
        $laposta->setApiToken($this->resolveApiKey());

        $lapostaId = $list->getLapostaId();
        $listName  = $list->getListName();

        if (empty($listName)) {
            $listName = '(Empty List Name - Magento)';

            $list->setListName($listName);
        }

        if (empty($lapostaId)) {
            $lapostaId = $laposta->addGroup($listName);

            $list->setLapostaId($lapostaId);
        }
        else {
            $laposta->updateGroup($lapostaId, $listName);
        }

        $this->resetWebhooks($list);

        $list->setSyncTime(Mage::getModel('lapostaconnect/list')->getCollection()->formatDate(time()));

        return $this;
    }

    /**
     * Reset the webhooks with a new token
     *
     * @param Laposta_Connect_Model_List $list
     *
     * @return $this
     */
    protected function resetWebhooks(Laposta_Connect_Model_List $list)
    {
        /** @var $laposta Laposta_Connect_Helper_Laposta */
        $laposta = Mage::helper('lapostaconnect/laposta');
        $laposta->setApiToken($this->resolveApiKey());

        $lapostaListId = $list->getLapostaId();
        $current       = $laposta->getHooks($lapostaListId, Mage::getBaseUrl());
        $token         = base_convert(rand(PHP_INT_MAX / 3, PHP_INT_MAX), 10, 36);
        $hookUrl       = Mage::getBaseUrl() . 'lapostaconnect/webhook?t=' . $token;

        foreach ($current as $hookData) {
            if (!isset($hookData['webhook']['webhook_id'])) {
                continue;
            }

            $laposta->removeHook($lapostaListId, $hookData['webhook']['webhook_id']);
        }

        $laposta->addHook($lapostaListId, $hookUrl);

        $list->setWebhookToken($token);

        return $this;
    }

    /**
     * Sync fields with laposta
     *
     * @param Laposta_Connect_Model_List                    $list
     * @param Laposta_Connect_Model_Mysql4_Field_Collection $fields
     *
     * @return $this
     */
    public function syncFields(
        Laposta_Connect_Model_List $list,
        Laposta_Connect_Model_Mysql4_Field_Collection $fields
    ) {
        if (Mage::helper('lapostaconnect')->config('active') !== 1) {
            return $this;
        }

        $lapostaListId = $list->getLapostaId();

        if (empty($lapostaListId) || $fields->count() === 0) {
            return $this;
        }

        /** @var $laposta Laposta_Connect_Helper_Laposta */
        $laposta = Mage::helper('lapostaconnect/laposta');
        $laposta->setApiToken($this->resolveApiKey());

        $current      = $this->resolveCurrentFields($lapostaListId);
        $synchronised = array();

        /** @var $field Laposta_Connect_Model_Field */
        foreach ($fields as $field) {
            $lapostaFieldId = $field->getLapostaId();
            $synchronised[] = $lapostaFieldId;

            if (!empty($lapostaFieldId) && strtotime($field->getUpdatedTime()) <= strtotime($field->getSyncTime())
            ) {
                continue;
            }

            $fieldName     = $field->getFieldName();
            $fieldConfig   = $this->resolveFieldConfig($fieldName);
            $fieldRelation = $field->getFieldRelation();

            // TODO: Use field type and field options resolver
            if (empty($lapostaFieldId)) {
                $result = $laposta->addField(
                    $lapostaListId,
                    $fieldRelation,
                    $fieldConfig['type'],
                    $fieldConfig['options'],
                    $fieldConfig['default'],
                    $fieldConfig['required'],
                    $fieldConfig['showInForm'],
                    $fieldConfig['showInList']
                );

                $lapostaFieldId  = $result['id'];
                $lapostaFieldTag = $result['tag'];

                $field->setLapostaId($lapostaFieldId);
            }
            else {
                $lapostaFieldTag = $laposta->updateField(
                    $lapostaListId,
                    $lapostaFieldId,
                    $fieldRelation,
                    $fieldConfig['type'],
                    $fieldConfig['options'],
                    $fieldConfig['default'],
                    $fieldConfig['required'],
                    $fieldConfig['showInForm'],
                    $fieldConfig['showInList']
                );
            }

            $field->setLapostaTag($lapostaFieldTag);
            $field->setSyncTime($fields->formatDate(time()));
        }

        $remove = array_diff($current, $synchronised);

        foreach ($remove as $lapostaFieldId) {
            $laposta->removeField($lapostaListId, $lapostaFieldId);
        }

        return $this;
    }

    /**
     * Resolve the field configuration
     *
     * @param string $fieldName
     *
     * @return array
     */
    protected function resolveFieldConfig($fieldName)
    {
        $result = array(
            'type'       => Laposta_Connect_Helper_Laposta::FIELD_TYPE_TEXT,
            'options'    => array(),
            'default'    => '',
            'required'   => false,
            'showInForm' => true,
            'showInList' => true,
        );

        if (isset($this->fieldConfigMap[$fieldName])) {
            $result = array_replace($result, $this->fieldConfigMap[$fieldName]);
        }

        return $result;
    }

    /**
     * Resolve the field options
     *
     * @param string $fieldName
     *
     * @return array
     */
    protected function resolveFieldOptions($fieldName)
    {
        if (isset($this->optionsMap[$fieldName])) {
            return $this->optionsMap[$fieldName];
        }

        return array();
    }

    /**
     * Get the list of fields registered with Laposta for the given list id.
     *
     * @param string $lapostaListId
     *
     * @return array
     */
    protected function resolveCurrentFields($lapostaListId)
    {
        /** @var $laposta Laposta_Connect_Helper_Laposta */
        $laposta = Mage::helper('lapostaconnect/laposta');
        $laposta->setApiToken($this->resolveApiKey());

        $current = $laposta->getFields($lapostaListId);

        if (empty($current) || !is_array($current)) {
            return array();
        }

        $result = array();

        foreach ($current as $field) {
            if (!isset($field['field']['field_id'])) {
                continue;
            }

            $result[] = $field['field']['field_id'];
        }

        return $result;
    }

    /**
     * Synchronise the subscribers
     *
     * @param Laposta_Connect_Model_Mysql4_Subscriber_Collection $subscribers
     *
     * @return $this
     */
    public function syncSubscribers(
        Laposta_Connect_Model_Mysql4_Subscriber_Collection $subscribers
    ) {
        if (Mage::helper('lapostaconnect')->config('active') !== '1') {
            return $this;
        }

        /** @var $laposta Laposta_Connect_Helper_Laposta */
        $laposta = Mage::helper('lapostaconnect/laposta');
        $laposta->setApiToken($this->resolveApiKey());

        /** @var $lists Laposta_Connect_Model_Mysql4_List_Collection */
        $lists     = Mage::getModel('lapostaconnect/list')->getCollection();
        $listIdMap = array_combine(
            $lists->getColumnValues('list_id'),
            $lists->getColumnValues('laposta_id')
        );

        foreach ($listIdMap as $lapostaListId) {
            $laposta->disableHooks($lapostaListId, Mage::getBaseUrl());
        }

        /** @var $subscriber Laposta_Connect_Model_Mysql4_Subscriber */
        foreach ($subscribers as $subscriber) {
            $customerId      = $subscriber->getCustomerId();
            $lapostaMemberId = $subscriber->getLapostaId();
            $lapostaListId   = $listIdMap[$subscriber->getListId()];

            if (empty($customerId) && empty($lapostaMemberId)) {
                $subscriber->delete($subscriber);

                continue;
            }

            if (empty($customerId)) {
                $laposta->removeContact($lapostaListId, $lapostaMemberId);
            }
            else {
                $customer = Mage::getModel('customer/customer')->load($customerId);
                $data     = array(); // TODO: Resolve the correct data

                if (empty($lapostaMemberId)) {
                    $laposta->addContact($lapostaListId, '', $customer->getEmail(), $data);
                }
                else {
                    $laposta->updateContact($lapostaListId, $lapostaMemberId, '', $customer->getEmail());
                }
            }
        }

        foreach ($listIdMap as $lapostaListId) {
            $laposta->enableHooks($lapostaListId, Mage::getBaseUrl());
        }

        return $this;
    }
}