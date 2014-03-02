<?php


class Laposta_Connect_Helper_Sync extends Mage_Core_Helper_Abstract
{
    /**
     * Sync the list configuration with Laposta
     *
     * @param Laposta_Connect_Model_List $list
     *
     * @return $this
     */
    public function syncList(Laposta_Connect_Model_List $list)
    {
        /** @var $laposta Laposta_Connect_Helper_Laposta */
        $laposta = Mage::helper('lapostaconnect/laposta');
        $laposta->setApiToken(Mage::getStoreConfig('lapostaconnect/laposta/api_key'));

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

        $list->setSyncTime(date('Y-m-d H:i:s'));

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
        $lapostaListId = $list->getLapostaId();

        if (empty($lapostaListId) || $fields->count() === 0) {
            return $this;
        }

        /** @var $laposta Laposta_Connect_Helper_Laposta */
        $laposta = Mage::helper('lapostaconnect/laposta');
        $laposta->setApiToken(Mage::getStoreConfig('lapostaconnect/laposta/api_key'));

        $current = $this->resolveCurrentFields($lapostaListId);
        $synchronised = array();

        /** @var $field Laposta_Connect_Model_Field */
        foreach ($fields as $field) {
            $lapostaFieldId = $field->getLapostaId();

            // TODO: Use field type and field options resolver
            if (empty($lapostaFieldId)) {
                $lapostaFieldId = $laposta->addField(
                    $lapostaListId,
                    $field->getFieldName(),
                    Laposta_Connect_Helper_Laposta::FIELD_TYPE_TEXT
                );

                $field->setLapostaId($lapostaFieldId);
            }
            else {
                $laposta->updateField(
                    $lapostaListId,
                    $lapostaFieldId,
                    $field->getFieldName(),
                    Laposta_Connect_Helper_Laposta::FIELD_TYPE_TEXT
                );
            }

            $field->setSyncTime(date('Y-m-d H:i:s'));

            $synchronised[] = $lapostaFieldId;
        }

        $remove = array_diff($current, $synchronised);

        foreach ($remove as $lapostaFieldId) {
            $laposta->removeField($lapostaListId, $lapostaFieldId);
        }

        return $this;
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
        $laposta->setApiToken(Mage::getStoreConfig('lapostaconnect/laposta/api_key'));

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

} 
