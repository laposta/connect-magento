<?php

require_once Mage::getBaseDir('lib') . '/Laposta/Laposta.php';

class Laposta_Connect_Helper_Laposta extends Mage_Core_Helper_Abstract
{
    const FIELD_TYPE_TEXT = 'text';

    const FIELD_TYPE_NUMERIC = 'numeric';

    const FIELD_TYPE_DATE = 'date';

    const FIELD_TYPE_SELECT_SINGLE = 'select_single';

    const FIELD_TYPE_SELECT_MULTI = 'select_multiple';

    /**
     * @param $apiKey
     */
    public function setApiToken($apiKey)
    {
        Laposta::setApiKey($apiKey);
    }

    /**
     * Get all groups from the API
     *
     * @return array
     */
    public function getGroups()
    {
    }

    /**
     * Get all contacts from the API
     *
     * @return array
     */
    public function getContacts()
    {
    }

    /**
     * Add a new group
     *
     * @param string $title
     *
     * @return string
     */
    public function addGroup($title)
    {
        $list   = new Laposta_List();
        $result = $list->create(
            array(
                'name' => $title,
            )
        );

        return $result['list']['list_id'];
    }

    /**
     * Add a new contact
     *
     * @param string $listId
     * @param string $ip
     * @param string $email
     * @param array  $fields
     *
     * @return string
     */
    public function addContact($listId, $ip, $email, $fields = array())
    {
        $member = new Laposta_Member($listId);
        $source = Mage::getBaseUrl(
            Mage_Core_Model_Store::URL_TYPE_LINK,
            Mage::app()->getStore()->isCurrentlySecure()
        );
        $data   = array(
            'ip'            => $this->resolveIp($ip),
            'email'         => $email,
            'source_url'    => $source,
            'custom_fields' => $this->denormalizeFields($listId, $fields),
        );

        $result = $member->create($data);

        return $result['member']['member_id'];
    }

    /**
     * Denormalize member data fields for Laposta
     *
     * @param string $listId
     * @param array  $data
     *
     * @return array
     */
    protected function denormalizeFields($listId, $data)
    {
        // TODO: Map customer data to laposta field tags

        return $data;
    }

    /**
     * Normalize member data fields received from Laposta
     *
     * @param string $listId
     * @param array  $data
     *
     * @return array
     */
    protected function normalizeFields($listId, $data)
    {
        // TODO: Map laposta field tags to customer data

        return $data;
    }

    /**
     * Resolve a valid IP address.
     *
     * @param string $ip
     *
     * @return string
     */
    protected function resolveIp($ip)
    {
        if (!empty($ip)) {
            return $ip;
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '127.0.0.1';
    }

    /**
     * Update an existing contact
     *
     * @param string $listId
     * @param string $memberId
     * @param string $ip
     * @param string $email
     * @param array  $data
     *
     * @return $this
     */
    public function updateContact($listId, $memberId, $ip, $email, $data = array())
    {
        $member = new Laposta_Member($listId);
        $source = Mage::getBaseUrl(
            Mage_Core_Model_Store::URL_TYPE_LINK,
            Mage::app()->getStore()->isCurrentlySecure()
        );
        $data   = array(
            'ip'            => $this->resolveIp($ip),
            'email'         => $email,
            'source_url'    => $source,
            'custom_fields' => $this->denormalizeFields($listId, $data),
        );

        $member->update($memberId, $data);

        return $this;
    }

    /**
     * Modify an existing group
     *
     * @param string $listId
     * @param string $title
     *
     * @return $this
     */
    public function updateGroup($listId, $title)
    {
        $list = new Laposta_List();

        $list->update(
            $listId,
            array(
                'name' => $title,
            )
        );

        return $this;
    }

    /**
     * Add a field to a group
     *
     * @param string $listId
     * @param string $name
     * @param string $type
     * @param array  $options
     * @param string $defaultValue
     * @param bool   $required
     * @param bool   $showInForm
     * @param bool   $showInList
     *
     * @return string
     */
    public function addField(
        $listId,
        $name,
        $type = self::FIELD_TYPE_TEXT,
        $options = array(),
        $defaultValue = '',
        $required = false,
        $showInForm = true,
        $showInList = true
    ) {
        $lapField = new Laposta_Field($listId);
        $meta     = array(
            'name'         => $name,
            'datatype'     => $type,
            'defaultvalue' => $defaultValue,
            'required'     => $required ? 'true' : 'false',
            'in_form'      => $showInForm ? 'true' : 'false',
            'in_list'      => $showInList ? 'true' : 'false',
        );

        if ($type === self::FIELD_TYPE_SELECT_MULTI || $type === self::FIELD_TYPE_SELECT_SINGLE) {
            $meta['options'] = $options;
        }

        $result = $lapField->create($meta);

        return array(
            'id'  => $result['field']['field_id'],
            'tag' => $result['field']['tag'],
        );
    }

    /**
     * Update a field on a group
     *
     * @param string $listId
     * @param string $fieldId
     * @param string $name
     * @param string $type
     * @param array  $options
     * @param string $defaultValue
     * @param bool   $required
     * @param bool   $showInForm
     * @param bool   $showInList
     *
     * @return $this
     */
    public function updateField(
        $listId,
        $fieldId,
        $name,
        $type = self::FIELD_TYPE_TEXT,
        $options = array(),
        $defaultValue = '',
        $required = false,
        $showInForm = true,
        $showInList = true
    ) {
        $lapField = new Laposta_Field($listId);
        $data     = array(
            'name'         => $name,
            'datatype'     => $type,
            'defaultvalue' => $defaultValue,
            'required'     => $required ? 'true' : 'false',
            'in_form'      => $showInForm ? 'true' : 'false',
            'in_list'      => $showInList ? 'true' : 'false',
        );

        if ($type === self::FIELD_TYPE_SELECT_MULTI || $type === self::FIELD_TYPE_SELECT_SINGLE) {
            $data['options'] = $options;
        }

        $lapField->update($fieldId, $data);

        return $this;
    }

    /**
     * Remove a field
     *
     * @param string $listId
     * @param string $fieldId
     *
     * @return $this
     */
    public function removeField($listId, $fieldId)
    {
        $lapField = new Laposta_Field($listId);
        $lapField->delete($fieldId);

        return $this;
    }

    /**
     * Get a single group by its identifier
     *
     * @param string $listId
     *
     * @return array
     */
    public function getGroup($listId)
    {
        $list   = new Laposta_List();
        $result = $list->get($listId);

        return $result['list'];
    }

    /**
     * Get a single contact by its identifier
     *
     * @param string $listId
     * @param string $memberId
     *
     * @return array
     */
    public function getContact($listId, $memberId)
    {
        $member = new Laposta_Member($listId);
        $result = $member->get($memberId);

        return $this->normalizeContact($result);
    }

    /**
     * Convert data from the source into a native contact object.
     *
     * @param array $data
     *
     * @return Contact
     * @throws RuntimeException
     */
    public function normalizeContact(array $data)
    {
        if (!isset($data['memeber'])) {
            return $data;
        }

        $data['member']['custom_fields'] = $this->normalizeFields(
            $data['member']['list_id'],
            $data['member']['custom_fields']
        );

        return $data;
    }

    /**
     * Get a list of fields for the given groupId
     *
     * @param string $listId
     *
     * @return array
     */
    public function getFields($listId)
    {
        $field  = new Laposta_Field($listId);
        $result = $field->all();

        return $result['data'];
    }

    /**
     * Remove all lists.
     *
     * @param string $listId
     *
     * @return $this
     */
    public function removeLists($listId)
    {
        $list = new Laposta_List();
        $list->delete($listId);

        return $this;
    }

    /**
     * Add a webhook
     *
     * @param string $listId
     * @param string $hookUrl
     *
     * @return array
     */
    public function addHook($listId, $hookUrl)
    {
        $hooks  = array();
        $events = array(
            'subscribed',
            'modified',
            'deactivated',
        );

        foreach ($events as $event) {
            $hook = new Laposta_Webhook($listId);

            $result = $hook->create(
                array(
                    'event'   => $event,
                    'url'     => $hookUrl,
                    'blocked' => 'false',
                )
            );

            $hooks[] = $result['webhook']['webhook_id'];
        }

        return $hooks;
    }

    /**
     * Get a list of all webhooks
     *
     * @param string $listId
     *
     * @return array
     */
    public function getHooks($listId)
    {
        $hook   = new Laposta_Webhook($listId);
        $result = $hook->all();

        return $result['data'];
    }

    /**
     * Disable all webhooks.
     *
     * @param $listId
     *
     * @return $this
     */
    public function disableHooks($listId)
    {
        $hooks = $this->getHooks($listId);

        foreach ($hooks as $data) {
            $hook = new Laposta_Webhook($listId);

            $hook->update(
                $data['webhook']['webhook_id'],
                array('blocked' => 'true')
            );
        }

        return $this;
    }

    /**
     * Re-enable all webhooks.
     *
     * @param $listId
     *
     * @return $this
     */
    public function enableHooks($listId)
    {
        $hooks = $this->getHooks($listId);

        foreach ($hooks as $data) {
            $hook = new Laposta_Webhook($listId);

            $hook->update(
                $data['webhook']['webhook_id'],
                array('blocked' => 'false')
            );
        }

        return $this;
    }
} 
