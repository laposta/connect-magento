<?php

$installer = $this;

$installer->startSetup();

$installer->run(
    "
    CREATE TABLE IF NOT EXISTS `{$this->getTable('laposta_subscriber')}` (
      `subscriber_id` int(11) unsigned NOT NULL auto_increment,
      `list_id` int(11) unsigned NOT NULL default 1,
      `customer_id` varchar(255) NOT NULL default '',
      `laposta_id` varchar(255) NOT NULL default '',
      `updated_time` datetime NULL,
      `sync_time` datetime NULL,
      PRIMARY KEY (`subscriber_id`),
      INDEX `list_id` (`list_id`),
      INDEX `customer_id` (`customer_id`),
      INDEX `laposta_id` (`laposta_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    "
);

$installer->run(
    "
    CREATE TABLE IF NOT EXISTS `{$this->getTable('laposta_field')}` (
      `field_id` int(11) unsigned NOT NULL auto_increment,
      `list_id` int(11) unsigned NOT NULL default 1,
      `field_name` varchar(255) NOT NULL default '',
      `field_relation` varchar(255) NOT NULL default '',
      `laposta_id` varchar(255) NOT NULL default '',
      `laposta_tag` varchar(255) NOT NULL default '',
      `updated_time` datetime NULL,
      `sync_time` datetime NULL,
      PRIMARY KEY (`field_id`),
      INDEX `list_id` (`list_id`),
      INDEX `laposta_id` (`laposta_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    "
);

$installer->run(
    "
    CREATE TABLE IF NOT EXISTS `{$this->getTable('laposta_list')}` (
      `list_id` int(11) unsigned NOT NULL auto_increment,
      `list_name` varchar(255) NOT NULL default '',
      `laposta_id` varchar(255) NOT NULL default '',
      `webhook_token` varchar(255) NOT NULL default '',
      `updated_time` datetime NULL,
      `sync_time` datetime NULL,
      PRIMARY KEY (`list_id`),
      INDEX `webhook_token` (`webhook_token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    "
);

$installer->endSetup();
