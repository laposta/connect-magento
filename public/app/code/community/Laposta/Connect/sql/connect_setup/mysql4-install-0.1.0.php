<?php

$installer = $this;

$installer->startSetup();

$installer->run(
    "
    CREATE TABLE IF NOT EXISTS {$this->getTable('subscriber')} (
      `subscriber_id` int(11) unsigned NOT NULL auto_increment,
      `customer_id` varchar(255) NOT NULL default '',
      `laposta_id` varchar(255) NOT NULL default '',
      `updated_time` datetime NULL,
      `sync_time` datetime NULL,
      PRIMARY KEY (`subscriber_id`),
      INDEX `customer_id` (`customer_id`),
      INDEX `laposta_id` (`laposta_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    "
);

$installer->run(
    "
    CREATE TABLE IF NOT EXISTS {$this->getTable('field')} (
      `field_id` int(11) unsigned NOT NULL auto_increment,
      `field_name` varchar(255) NOT NULL default '',
      `field_relation` varchar(255) NOT NULL default '',
      `laposta_id` varchar(255) NOT NULL default '',
      `updated_time` datetime NULL,
      `sync_time` datetime NULL,
      PRIMARY KEY (`field_id`),
      INDEX `laposta_id` (`laposta_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    "
);

$installer->run(
    "
    CREATE TABLE IF NOT EXISTS {$this->getTable('config')} (
      `config_id` int(11) unsigned NOT NULL auto_increment,
      `path` varchar(255) NOT NULL default '',
      `value` varchar(255) NOT NULL default '',
      PRIMARY KEY (`config_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    "
);

$installer->endSetup();
