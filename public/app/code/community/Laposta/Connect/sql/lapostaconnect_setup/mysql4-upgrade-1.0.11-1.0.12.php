<?php

$installer = $this;

$installer->startSetup();

$installer->run("
ALTER TABLE `{$this->getTable('laposta_subscriber')}` ADD COLUMN `newsletter_subscriber_id` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `customer_id`
");

$installer->run("
ALTER TABLE `{$this->getTable('laposta_subscriber')}` ADD INDEX `newsletter_subscriber_id` (`newsletter_subscriber_id`)
");
