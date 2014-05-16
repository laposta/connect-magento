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
        $listToken = Mage::app()->getRequest()->getParam('t');
        $data      = $this->getInputStream();

        $this->consumeEvents($listToken, $data);
    }

    /**
     * Consume the given events to update contacts in google.
     *
     * @param string $listToken
     * @param string $eventsJson
     *
     * @throws \RuntimeException
     * @throws \Exception
     * @return $this
     */
    public function consumeEvents($listToken, $eventsJson)
    {
        $listToken = filter_var($listToken, FILTER_SANITIZE_STRING);
        $decoded   = json_decode($eventsJson, true);

        /** @var $lists Laposta_Connect_Model_Mysql4_List_Collection */
        $lists = Mage::getModel('lapostaconnect/list')->getCollection();
        /** @var $list Laposta_Connect_Model_List */
        $list  = array_shift(
            $lists->getItemsByColumnValue('webhook_token', $listToken)
        );

        $this->log("Found list using webhook token '$listToken'", $list);

        if (!$list instanceof Laposta_Connect_Model_List) {
            return $this->log("Unable to consume events. '$listToken' is not a valid webhook token.");
        }

        $this->log("Consuming events for client '$listToken'", $eventsJson);

        if ($decoded === false) {
            return $this->log("Events data could not be parsed. Input is not valid JSON.");
        }

        if (!isset($decoded['data']) || !is_array($decoded['data'])) {
            return $this;
        }

        foreach ($decoded['data'] as $event) {
            try {
                $this->consumeEvent($event, $list);
            }
            catch (Exception $e) {
                $this->log("{$e->getMessage()} on line '{$e->getLine()}' of '{$e->getFile()}'");
            }
        }

        return $this;
    }

    /**
     * Retrieve date from the input stream
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getInputStream()
    {
        $source = @fopen('php://input', 'r');

        if (!is_resource($source)) {
            throw new InvalidArgumentException('Expected parameter 1 to be an open-able resource');
        }

        $data = null;

        while ($buffer = fread($source, 1024)) {
            $data .= $buffer;
        }

        fclose($source);

        return $data;
    }

    /**
     * Submit a log entry
     *
     * @param string $message
     * @param mixed  $data
     *
     * @return $this
     */
    protected function log($message, $data = null)
    {
        Mage::helper('lapostaconnect')->log(
            array(
                'message' => $message,
                'data'    => $data,
            )
        );

        return $this;
    }

    /**
     * Consume an event from Laposta
     *
     * @param array                      $event
     * @param Laposta_Connect_Model_List $list
     *
     * @return $this
     */
    protected function consumeEvent($event, Laposta_Connect_Model_List $list)
    {
        if (empty($event['type']) || $event['type'] !== 'member' || !isset($event['data'])) {
            return $this;
        }
        if (!isset($event['data']['list_id']) || !isset($event['data']['member_id'])) {
            return $this;
        }

        $listId   = $event['data']['list_id'];
        $memberId = $event['data']['member_id'];
        $status   = isset($event['data']['state']) ? $event['data']['state'] : 'cleaned';

        if ($list->getData('laposta_id') !== $listId) {
            return $this->log("Resolved list id '{$list->getData('laposta_id')}' does not match provided list id '$listId'.");
        }

        /** @var $subscribers Laposta_Connect_Model_Mysql4_Subscriber_Collection */
        $subscribers = Mage::getModel('lapostaconnect/subscriber')->getCollection();
        /** @var $subscriber Laposta_Connect_Model_Subscriber */
        $subscriber  = $subscribers->getItemByColumnValue('laposta_id', $memberId);

        if (!$subscriber instanceof Laposta_Connect_Model_Subscriber) {
            return $this->log("Subscriber for laposta id '$memberId' not found.");
        }

        /** @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer')->load($subscriber->getData('customer_id'));

        if (!$customer instanceof Mage_Customer_Model_Customer) {
            return $this->log("Customer for subscriber with laposta id '$memberId' not found.");
        }

        /** @var $newsletterSubscriberModel Mage_Newsletter_Model_Subscriber */
        $newsletterSubscriberModel = Mage::getModel('newsletter/subscriber');
        /** @var $newsletterSubscriber Mage_Newsletter_Model_Subscriber */
        $newsletterSubscriber = $newsletterSubscriberModel->loadByCustomer($customer);
        $newsletterSubscriber->setCustomerId($subscriber->getData('customer_id'));
        $newsletterSubscriber->setEmail($customer->getEmail());
        $newsletterSubscriber->setStoreId($customer->getStore()->getId());

        if ($status !== 'active') {
            $customer->setIsSubscribed(false);
            $newsletterSubscriber->unsubscribe();

            $this->log("Customer '{$customer->getEmail()}' for subscriber with laposta id '$memberId' has been unsubscribed.");
        }
        else {
            $customer->setIsSubscribed(true);
            $newsletterSubscriber->subscribeCustomer($customer);

            $this->log("Customer '{$customer->getEmail()}' for subscriber with laposta id '$memberId' has been subscribed.");
        }

        $customer->save();

        return $this;
    }
}
