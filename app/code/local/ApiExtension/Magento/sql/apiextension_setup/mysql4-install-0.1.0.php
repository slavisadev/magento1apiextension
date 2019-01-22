<?php

$installer = $this;
$installer->startSetup();

$installer->run("DROP TABLE IF EXISTS {$this->getTable('apiExtension_webhooks')};
     CREATE TABLE {$this->getTable('apiExtension_webhooks')} (
        `id` int not null auto_increment,
        `code` varchar(100),
        `description` varchar(100),
        `url` varchar(255),
        `token` varchar(255),
        `data` varchar(255),
        `active` int not null,
      PRIMARY KEY (`id`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

     ALTER TABLE {$this->getTable('apiExtension_webhooks')} ADD UNIQUE(code(100), url(255), data(255));");

$installer->endSetup();
