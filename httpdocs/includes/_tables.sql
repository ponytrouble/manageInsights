DROP TABLE IF EXISTS `springcreek`.`scga_accounts`;
CREATE TABLE  `springcreek`.`scga_accounts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user` varchar(255) character set latin1 default NULL,
  `user_email` varchar(255) default NULL,
  `application` varchar(255) character set latin1 default NULL,
  `name` varchar(255) character set latin1 NOT NULL,
  `category` varchar(255) character set latin1 default NULL,
  `secret` varchar(255) character set latin1 default NULL,
  `access_token` varchar(255) character set latin1 default NULL,
  `is_deleted` int(10) default NULL,
  `date_added` datetime default NULL,
  `date_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=161 DEFAULT CHARSET=utf8;




DROP TABLE IF EXISTS `springcreek`.`scga_account_secrets`;
CREATE TABLE  `springcreek`.`scga_account_secrets` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `application` varchar(255) NOT NULL default '',
  `secret` varchar(255) default NULL,
  `date_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `index_2` (`application`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `springcreek`.`scga_account_silos`;
CREATE TABLE  `springcreek`.`scga_account_silos` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `application` varchar(255) NOT NULL default '',
  `silo_id` varchar(255) default NULL,
  `date_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `index_2` USING BTREE (`application`)
) ENGINE=MyISAM AUTO_INCREMENT=548 DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `springcreek`.`scga_batch`;
CREATE TABLE  `springcreek`.`scga_batch` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `application` varchar(255) character set latin1 default NULL,
  `query` tinytext,
  `query_result` tinytext,
  `query_type` varchar(255) character set latin1 default NULL,
  `message` tinytext character set latin1,
  `date_added` datetime default NULL,
  `date_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=33013 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `springcreek`.`scga_jobs`;
CREATE TABLE  `springcreek`.`scga_jobs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `application` varchar(255) default NULL,
  `start_date` varchar(255) default NULL,
  `end_date` varchar(255) default NULL,
  `is_deleted` int(1) default NULL,
  `date_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;



DROP TABLE IF EXISTS `springcreek`.`scga_results`;
CREATE TABLE  `springcreek`.`scga_results` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `application` varchar(255) default NULL,
  `field` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  `period` varchar(255) default NULL,
  `end_time` varchar(255) default NULL,
  `date_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

