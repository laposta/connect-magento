<?php


class Laposta_Connect_Helper_Fields extends Mage_Core_Helper_Abstract
{
    /**
     * @var array
     */
    protected $cache = array();

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * Warm up the cache for easier data retrieval
     */
    protected function initialize()
    {
        if ($this->initialized) {
            return;
        }

        /** @var $fields Laposta_Connect_Model_Mysql4_Field_Collection */
        $fields = Mage::getModel('lapostaconnect/field')->getCollection()->load();

        /** @var $field Laposta_Connect_Model_Field */
        foreach ($fields as $field) {
            $listId = $field->getData('list_id');
            $fieldName = $field->getData('field_name');
            $lapostaTag = $field->getData('laposta_tag');

            $this->cache[$listId][$fieldName] = $lapostaTag;
        }

        $this->initialized = true;
    }

    /**
     * Retrieve a list of field_name => laposta_tag relations by listId
     *
     * @param int $listId
     *
     * @return array
     */
    public function getByListId($listId)
    {
        $this->initialize();

        if (!isset($this->cache[$listId])) {
            return array();
        }

        return $this->cache[$listId];
    }
} 
