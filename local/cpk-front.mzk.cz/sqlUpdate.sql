/* Set longer username in user table */
ALTER TABLE user CHANGE username username VARCHAR(64);

/* Create indexed eppn for purposes of connected federative identities */
ALTER TABLE user_card ADD COLUMN eppn VARCHAR(64);
CREATE UNIQUE INDEX user_card_eppn_uq ON user_card(eppn);

/* Create and fill table representing citation styles */
CREATE TABLE IF NOT EXISTS `citation_style` (
  `id` int(11) NOT NULL,
  `description` VARCHAR(32),
  `value` VARCHAR(8) UNIQUE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

ALTER TABLE `citation_style`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `citation_style`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;

INSERT INTO `citation_style` (`id`, `description`, `value`) 
  VALUES (NULL, 'ČSN ISO 690', '38673'),
         (NULL, 'Harvard', '3'), 
         (NULL, 'NISO/ANSI Z39.29 (2005)', '4'),
         (NULL, 'MLA (7th edition)', '5'), 
         (NULL, 'Turabian (7th edition)', '6'), 
         (NULL, 'Chicago (16th edition)', '7'), 
         (NULL, 'IEEE', '8'), 
         (NULL, 'CSE', '9'), 
         (NULL, 'CSE NY', '10'), 
         (NULL, 'APA', '11'), 
         (NULL, 'ISO 690', '12');


/* Create table for users settings */
CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `citation_style` int(8) NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`);

ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;

ALTER TABLE `user_settings`
  ADD FOREIGN KEY (user_id) REFERENCES user(id);
  
ALTER TABLE `user_settings`
  ADD FOREIGN KEY (citation_style) REFERENCES citation_style(id);

/* Create column record_per_page in user_settings table */
ALTER TABLE `user_settings` ADD `records_per_page` TINYINT NULL ;

/* Create column record_per_page in user_settings table */
ALTER TABLE `user_settings` ADD `sorting` VARCHAR(40) NULL ;

/* Create table system for checking DB version */
CREATE TABLE IF NOT EXISTS `system` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(32) NOT NULL,
  `value` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_2` (`key`),
  UNIQUE KEY `id` (`id`),
  KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

/* Increment this column on every sqlUpdate.sql update  */
INSERT INTO `system` (`id`, `key`, `value`) VALUES
(1, 'DB_VERSION', '1');

/* Create table for storing content of Portal pages */
DROP TABLE IF EXISTS `portal_pages`;
CREATE TABLE `portal_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `pretty_url` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `language_code` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `published` tinyint(1) NOT NULL,
  `placement` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `position` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `order_priority` tinyint(4) NOT NULL,
  `last_modified_timestamp` datetime NOT NULL,
  `last_modified_user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `title` (`title`),
  UNIQUE KEY `pretty_url` (`pretty_url`),
  KEY `language_code` (`language_code`),
  KEY `pretty_url_2` (`pretty_url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;
UPDATE `system` SET `value`='3' WHERE `key`='DB_VERSION';

ALTER TABLE `user_card`
  ADD `major` VARCHAR(100) NULL,
  ADD INDEX ( `major` );
UPDATE `system` SET `value`='4' WHERE `key`='DB_VERSION';

/* Notifications */
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL, /* Even though this is user_id, it has to be called id because of Zend's incompatibility */
  `has_blocks` tinyint(1) NOT NULL,
  `has_fines` tinyint(1) NOT NULL,
  `has_overdues` tinyint(1) NOT NULL,
  `last_fetched` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `notifications`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `notifications`
ADD CONSTRAINT `user_card_id` FOREIGN KEY (`id`) REFERENCES `user_card` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE `system` SET `value`='5' WHERE `key`='DB_VERSION';

ALTER TABLE `notifications` ADD `blocks_read` BOOLEAN NOT NULL AFTER `has_overdues`, ADD `fines_read` BOOLEAN NOT NULL AFTER `blocks_read`, ADD `overdues_read` BOOLEAN NOT NULL AFTER `fines_read`;

UPDATE `system` SET `value`='6' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `institutions` (
  `id` int(11) unsigned NOT NULL,
  `source` varchar(100) NOT NULL DEFAULT '',
  `url` mediumtext NOT NULL,
  `type` enum('Aleph','NCIP') NULL,
  `timeout` int(3) unsigned DEFAULT 10 NOT NULL,
  `bot_username` varchar(64) DEFAULT NULL,
  `bot_password` varchar(64) DEFAULT NULL,
  `logo_url` mediumtext NOT NULL,
  `name_cs` mediumtext NOT NULL,
  `name_en` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE `institutions`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `source` (`source`);

ALTER TABLE `institutions`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;

UPDATE `system` SET `value`='7' WHERE `key`='DB_VERSION';

ALTER TABLE `user_card` ADD CONSTRAINT `home_library_link_1` FOREIGN KEY (`home_library`) REFERENCES `institutions`(`source`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `user` ADD CONSTRAINT `home_library_link_2` FOREIGN KEY (`home_library`) REFERENCES `institutions`(`source`) ON DELETE NO ACTION ON UPDATE NO ACTION;

UPDATE `system` SET `value`='8' WHERE `key`='DB_VERSION';

ALTER TABLE `institutions` ADD `entity_id` MEDIUMTEXT NOT NULL AFTER `url`;

UPDATE `system` SET `value`='9' WHERE `key`='DB_VERSION';

ALTER TABLE `institutions` CHANGE `type` `type` ENUM('Aleph','NCIP','IdP') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

UPDATE `system` SET `value`='10' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `aleph_configs` (
`id` int(10) unsigned NOT NULL,
  `source` varchar(100) NOT NULL DEFAULT '',
  `host` mediumtext NOT NULL,
  `dlfport` int(6) NOT NULL,
  `wwwuser` mediumtext NOT NULL,
  `wwwpasswd` mediumtext NOT NULL,
  `maxItemsParsed` int(2) NOT NULL,
  `default_patron` mediumtext NOT NULL,
  `hmac_key` mediumtext NOT NULL,
  `bib` mediumtext NOT NULL,
  `useradm` mediumtext NOT NULL,
  `admlib` mediumtext NOT NULL,
  `available_statuses` mediumtext NOT NULL,
  `dont_show_link` mediumtext NOT NULL,
  `default_required_date` varchar(8) NOT NULL,
  `send_language` tinyint(1) NOT NULL,
  `debug` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `aleph_configs`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `source` (`source`);

ALTER TABLE `aleph_configs`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE `aleph_configs`
ADD CONSTRAINT `institution_link` FOREIGN KEY (`source`) REFERENCES `institutions` (`source`) ON DELETE NO ACTION ON UPDATE NO ACTION;

UPDATE `system` SET `value`='11' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `xcncip2_configs` (
  `id` int(11) unsigned NOT NULL,
  `source` varchar(100) NOT NULL DEFAULT '',
  `url` mediumtext NOT NULL,
  `username` mediumtext,
  `password` mediumtext,
  `cacert` mediumtext NOT NULL,
  `agency` tinytext NOT NULL,
  `maximumItemsCount` int(11) NOT NULL DEFAULT '5',
  `hasUntrustedSSL` tinyint(1) NOT NULL,
  `cannotUseLUIS` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `xcncip2_configs`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `source` (`source`);

ALTER TABLE `xcncip2_configs`
ADD CONSTRAINT `institution_link_2` FOREIGN KEY (`source`) REFERENCES `institutions` (`source`) ON DELETE NO ACTION ON UPDATE NO ACTION;

UPDATE `system` SET `value`='12' WHERE `key`='DB_VERSION';

ALTER TABLE `institutions`
  DROP `bot_username`,
  DROP `bot_password`;

UPDATE `system` SET `value`='13' WHERE `key`='DB_VERSION';

ALTER TABLE `institutions` CHANGE `logo_url` `logo` MEDIUMTEXT NOT NULL;

ALTER TABLE `xcncip2_configs` ADD `paymentUrl` MEDIUMTEXT NULL AFTER `url`;

UPDATE `system` SET `value`='14' WHERE `key`='DB_VERSION';

ALTER TABLE `aleph_configs` ADD `type` MEDIUMTEXT NOT NULL COMMENT 'Which resolver to choose - xserver or solr?' AFTER `dont_show_link`, ADD `solrQueryField` MEDIUMTEXT NOT NULL COMMENT 'solrQueryField must contain the name of field within which are all the IDs located' AFTER `type`, ADD `itemIdentifier` MEDIUMTEXT NOT NULL COMMENT 'itemIdentifier must contain the name of field within which is the ID located' AFTER `solrQueryField`;

ALTER TABLE `aleph_configs` CHANGE `hmac_key` `hmac_key` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'This is used with creating a hash to verify prolongation url';

ALTER TABLE `aleph_configs` CHANGE `default_patron` `default_patron` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Default patron ID will be included into these queries, where we want to know item''s requestability'; 

ALTER TABLE `aleph_configs` CHANGE `maxItemsParsed` `maxItemsParsed` INT(2) NOT NULL COMMENT 'To disable this feature set this to -1 .. if you unset it, there will be set 10 as default';

ALTER TABLE `aleph_configs` CHANGE `default_required_date` `default_required_date` VARCHAR(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'redDate - A colon-separated list used to set the default "not required after" date for holds in the format days:months:years e.g. 0:1:0 will set a "not required after" date of 1 month from the current date';

UPDATE `aleph_configs` SET `type`='solr',`solrQueryField`='barcodes',`itemIdentifier`='barcode';

UPDATE `system` SET `value`='15' WHERE `key`='DB_VERSION';

-- Add aleph_mappings table
CREATE TABLE IF NOT EXISTS `aleph_mappings` (
`id` int(11) NOT NULL,
  `source` varchar(100) NOT NULL DEFAULT '',
  `barcode` enum('z304-address-1','z304-address-2','z304-address-3','z304-address-4') DEFAULT NULL,
  `fullname` enum('z304-address-1','z304-address-2','z304-address-3','z304-address-4') NOT NULL,
  `address` enum('z304-address-1','z304-address-2','z304-address-3','z304-address-4') NOT NULL,
  `city` enum('z304-address-1','z304-address-2','z304-address-3','z304-address-4') NOT NULL,
  `zip` enum('z304-zip') DEFAULT NULL,
  `email` enum('z304-email-address') DEFAULT NULL,
  `user_group` enum('z305-bor-status') DEFAULT NULL,
  `expiration` enum('z305-expiry-date') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `aleph_mappings`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `source` (`source`);

ALTER TABLE `aleph_mappings`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
ALTER TABLE `aleph_mappings`
ADD CONSTRAINT `aleph_mappings` FOREIGN KEY (`source`) REFERENCES `institutions` (`source`) ON DELETE NO ACTION ON UPDATE NO ACTION;

UPDATE `system` SET `value`='16' WHERE `key`='DB_VERSION';

-- Set the foreign key to point only to aleph institutions
ALTER TABLE `aleph_mappings` DROP FOREIGN KEY `aleph_mappings`; ALTER TABLE `aleph_mappings` ADD CONSTRAINT `aleph_mappings` FOREIGN KEY (`source`) REFERENCES `aleph_configs`(`source`) ON DELETE NO ACTION ON UPDATE NO ACTION;

UPDATE `system` SET `value`='17' WHERE `key`='DB_VERSION';

ALTER TABLE `aleph_mappings` ADD `phone` ENUM('z304-sms-number','z304-telephone-1','z304-telephone-2','z304-telephone-3','z304-telephone-4') NULL AFTER `email`;

UPDATE `system` SET `value`='18' WHERE `key`='DB_VERSION';

ALTER TABLE `user_card` DROP FOREIGN KEY `home_library_link_1`;
ALTER TABLE `user` DROP FOREIGN KEY `home_library_link_2`;
ALTER TABLE `aleph_mappings` DROP FOREIGN KEY `aleph_mappings`;
ALTER TABLE `aleph_configs` DROP FOREIGN KEY `institution_link`;
ALTER TABLE `xcncip2_configs` DROP FOREIGN KEY `institution_link_2`;
DROP TABLE `aleph_mappings`;
DROP TABLE `aleph_configs`;
DROP TABLE `xcncip2_configs`;
DROP TABLE `institutions`;

UPDATE `system` SET `value`='19' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `inst_translations` (
  `id` int(11) NOT NULL,
  `source` varchar(100) NOT NULL,
  `key` varchar(30) NOT NULL,
  `cs_translated` mediumtext NOT NULL,
  `en_translated` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table holding translations defined by institutions themselves';

ALTER TABLE `inst_translations`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `inst_translations` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;

UPDATE `system` SET `value`='20' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `inst_configs` (
  `id` int(11) NOT NULL,
  `source` varchar(10) NOT NULL DEFAULT '',
  `section` varchar(64) NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` mediumtext NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `inst_configs`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `inst_configs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

UPDATE `system` SET `value`='21' WHERE `key`='DB_VERSION';

ALTER TABLE `portal_pages` CHANGE `language_code` `language_code` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ;
UPDATE `portal_pages` SET language_code = 'cs-cpk-institutions' WHERE language_code = 'cs-cpk';
UPDATE `portal_pages` SET language_code = 'en-cpk-institutions' WHERE language_code = 'en-cpk';
UPDATE `system` SET `value`='22' WHERE `key`='DB_VERSION';

ALTER TABLE `portal_pages` ADD `group` INT NOT NULL;
UPDATE `system` SET `value`='23' WHERE `key`='DB_VERSION';

ALTER TABLE `user_settings` ADD `saved_institutions` TEXT NULL;
UPDATE `system` SET `value`='24' WHERE `key`='DB_VERSION';

ALTER TABLE `user_settings` CHANGE `saved_institutions` `saved_institutions` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
UPDATE `system` SET `value`='25' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `most_wanted` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `author` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
UPDATE `system` SET `value`='26' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `libraries_geolocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sigla` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  `latitude` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `longitude` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `town` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `district` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `region` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `zip` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  `street` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
UPDATE `system` SET `value`='27' WHERE `key`='DB_VERSION';

ALTER TABLE `libraries_geolocations` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT,
 CHANGE `sigla` `sigla` VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
 CHANGE `latitude` `latitude` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
 CHANGE `longitude` `longitude` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
 CHANGE `town` `town` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
 CHANGE `district` `district` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
 CHANGE `region` `region` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
 CHANGE `zip` `zip` VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
 CHANGE `street` `street` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

UPDATE `system` SET `value`='28' WHERE `key`='DB_VERSION';

ALTER TABLE `inst_configs` CHANGE `timestamp` `timestamp_requested` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CHANGE `user_id` `user_requested` INT(11) NOT NULL,
 ADD `timestamp_approved` TIMESTAMP NULL AFTER `timestamp_requested`,
 ADD `user_approved`  INT(11) NULL;

UPDATE `system` SET `value`='29' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `frontend` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_homepage_widget` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `second_homepage_widget` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `third_homepage_widget` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;


INSERT INTO `frontend` (`id`, `first_homepage_widget`, `second_homepage_widget`, `third_homepage_widget`) VALUES
(1, 'most_wanted', 'events', 'infobox');

UPDATE `system` SET `value`='30' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;


INSERT INTO `widgets` (`id`, `name`) VALUES
(1, 'most_wanted'),
(2, 'events'),
(3, 'infobox');

UPDATE `system` SET `value`='31' WHERE `key`='DB_VERSION';

ALTER TABLE `user_card` CHANGE `eppn` `eppn` VARCHAR(255) NULL DEFAULT NULL;

UPDATE `system` SET `value`='32' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `favorite_authors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authority_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `authority_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `short_note_cs` text COLLATE utf8_unicode_ci NOT NULL,
  `short_note_en` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

UPDATE `system` SET `value`='33' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `email_types` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(63) NOT NULL,
  `name` varchar(255) NOT NULL,
  `delay` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Email types for determining email type being sent with appropriate time delay';

INSERT INTO `email_types` (`id`, `key`, `name`, `delay`) VALUES
(1, 'idp_no_eppn', 'IdP gate did not send the eduPersonPrincipalName', '23:59:59'),
(2, 'ils_api_not_available', 'The API for ILS is not available at the moment', '02:30:00');

CREATE TABLE IF NOT EXISTS `email_delayer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `type` int(11) NOT NULL,
  `last_sent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Email delayer for determining delays between each warning email sent to an email';

ALTER TABLE `email_delayer`
  ADD CONSTRAINT `email_type` FOREIGN KEY (`type`) REFERENCES `email_types`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE `system` SET `value`='34' WHERE `key`='DB_VERSION';

CREATE TABLE IF NOT EXISTS `infobox` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title_en` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `title_cs` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `text_en` text COLLATE utf8_unicode_ci NOT NULL,
  `text_cs` text COLLATE utf8_unicode_ci NOT NULL,
  `date_from` datetime NOT NULL,
  `date_to` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO `infobox` (`id`, `title_en`, `title_cs`, `text_en`, `text_cs`, `date_from`, `date_to`) VALUES
(1, '...we are the champions?', '...jsme nejlepší?', 'We are the champions my friends.', 'Jsme nejlepší, protože je to tak.', '2016-06-17 00:00:00', '2016-09-30 00:00:00'),
(2, '...we are the champions? 2', '...jsme nejlepší? 2', 'We are the champions my friends.', 'Jsme nejlepší, protože je to tak.', '2016-06-17 00:00:00', '2016-09-30 00:00:00'),
(3, '...we are the champions? 3', '...jsme nejlepší? 3', 'We are the champions my friends.', 'Jsme nejlepší, protože je to tak.', '2016-06-17 00:00:00', '2016-09-30 00:00:00'),
(4, '...we are the champions? 4', '...jsme nejlepší? 4', 'We are the champions my friends.', 'Jsme nejlepší, protože je to tak.', '2016-06-17 00:00:00', '2016-09-30 00:00:00'),
(4, '...we are the champions? 5', '...jsme nejlepší? 5', 'We are the champions my friends.', 'Jsme nejlepší, protože je to tak.', '2016-06-17 00:00:00', '2016-09-30 00:00:00');


UPDATE `system` SET `value`='35' WHERE `key`='DB_VERSION';

ALTER TABLE `email_delayer` ADD `send_attempts_count` INT(11) NOT NULL DEFAULT '1' ;

UPDATE `system` SET `value`='36' WHERE `key`='DB_VERSION';

ALTER TABLE `email_delayer` ADD `source` VARCHAR(10) NOT NULL AFTER `email`;

UPDATE `system` SET `value`='37' WHERE `key`='DB_VERSION';

TRUNCATE `notifications`;

ALTER TABLE `notifications`
  DROP `has_blocks`,
  DROP `has_fines`,
  DROP `has_overdues`,
  DROP `blocks_read`,
  DROP `fines_read`,
  DROP `overdues_read`;

ALTER TABLE `notifications`
  ADD `type` INT(11) NOT NULL AFTER `id`,
  ADD KEY `notification_type` (`type`),
  ADD `shows` BOOLEAN NOT NULL AFTER `type`,
  ADD `read` BOOLEAN NOT NULL AFTER `shows`;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notification_type_id` FOREIGN KEY (`type`) REFERENCES `notification_types`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE `system` SET `value`='38' WHERE `key`='DB_VERSION';

ALTER TABLE `notifications` DROP FOREIGN KEY `user_card_id`;

TRUNCATE `notifications`;

ALTER TABLE `notifications` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notifications` ADD `user` INT(11) NOT NULL AFTER `id`;
ALTER TABLE `notifications` ADD  CONSTRAINT `notification_usercard_id` FOREIGN KEY (`user`) REFERENCES `user_card`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

INSERT INTO `notification_types` (`key`, `name_cs`, `name_en`) VALUES
  ('fines', 'Uživatel má nezaplacenou pokutu, či poplatek.', 'User has to pay a fine.'),
  ('blocks', 'Uživatel má blokaci.', 'User has block or trap.'),
  ('overdues', 'Uživatel má nevrácené výpůjčky', 'User has overdued items.'),
  ('user_dummy', 'Uživatel nemá propojenou žádnou knihovnu.', 'User has no library connected.');

UPDATE `system` SET `value`='39' WHERE `key`='DB_VERSION';

ALTER TABLE `widgets` ADD `display` VARCHAR( 32 ) NOT NULL ;

UPDATE `vufind`.`widgets` SET `display` = 'random' WHERE `widgets`.`id` =1;
UPDATE `vufind`.`widgets` SET `display` = 'default' WHERE `widgets`.`id` =2;
UPDATE `vufind`.`widgets` SET `display` = 'default' WHERE `widgets`.`id` =3;

INSERT INTO `vufind`.`widgets` (`id` , `name` , `display`) VALUES (NULL , 'favorite_authors', 'priority');

RENAME TABLE `widgets` TO `widget`;

CREATE TABLE IF NOT EXISTS `widget_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `widget_id` int(11) NOT NULL,
  `value` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `preferred_value` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=10 ;

INSERT INTO `widget_content` (`id`, `widget_id`, `value`, `preferred_value`) VALUES
(5, 1, 'nkp.NKC01-000038254', 0),
(6, 1, 'cbvk.CbvkUsCat_0474696', 0),
(7, 1, 'vkol.SVK01-000430490', 0),
(8, 4, 'auth.AUT10-000011623', 0),
(9, 4, 'auth.AUT10-000033729', 0);

DROP TABLE `most_wanted`;

DROP TABLE `favorite_authors`;

UPDATE `system` SET `value`='40' WHERE `key`='DB_VERSION';

ALTER TABLE `notifications` DROP FOREIGN KEY `notification_usercard_id`; 

TRUNCATE TABLE `notifications` ;

ALTER TABLE `notifications` ADD  CONSTRAINT `notification_user_id` FOREIGN KEY (`user`) REFERENCES `user`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `notifications` CHANGE `user` `user` INT(11) NULL DEFAULT NULL;

ALTER TABLE `notifications` ADD `user_card` INT(11) NULL DEFAULT NULL AFTER `user`, ADD INDEX (`user_card`);

ALTER TABLE `notifications` ADD CONSTRAINT `notification_usercard_id` FOREIGN KEY (`user_card`) REFERENCES `user_card`(`id`) ON DELETE CASCADE ON UPDATE CASCADE; 

UPDATE `system` SET `value`='41' WHERE `key`='DB_VERSION';

UPDATE `email_types` SET `delay` = '167:59:59' WHERE `email_types`.`key` = 'idp_no_eppn';
UPDATE `email_types` SET `delay` = '11:59:59' WHERE `email_types`.`key` = 'ils_api_not_available';

UPDATE `system` SET `value`='42' WHERE `key`='DB_VERSION';

ALTER TABLE `frontend` ADD `first_inspiration_widget` VARCHAR( 40 ) NOT NULL ,
ADD `second_inspiration_widget` VARCHAR( 40 ) NOT NULL ,
ADD `third_inspiration_widget` VARCHAR( 40 ) NOT NULL ,
ADD `fourth_inspiration_widget` VARCHAR( 40 ) NOT NULL ,
ADD `fifth_inspiration_widget` VARCHAR( 40 ) NOT NULL ,
ADD `sixth_inspiration_widget` VARCHAR( 40 ) NOT NULL ;

UPDATE `vufind`.`frontend` SET `first_inspiration_widget` = 'most_wanted',
`second_inspiration_widget` = 'favorite_authors',
`third_inspiration_widget` = 'infobox',
`fourth_inspiration_widget` = 'most_wanted',
`fifth_inspiration_widget` = 'favorite_authors',
`sixth_inspiration_widget` = 'infobox' WHERE `frontend`.`id` =1;

UPDATE `system` SET `value`='43' WHERE `key`='DB_VERSION';

ALTER TABLE `widget_content` ADD `description` TEXT NULL ;
ALTER TABLE `widget_content` CHANGE `description` `description_cs` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `widget_content` ADD `description_en` TEXT NULL ;
UPDATE `widget_content` SET `description_cs`='Defaultní popisek' WHERE `widget_id`=4
UPDATE `widget_content` SET `description_en`='Default description' WHERE `widget_id`=4
UPDATE `system` SET `value`='44' WHERE `key`='DB_VERSION';

DELETE FROM `citation_style` WHERE value='4'
UPDATE `system` SET `value`='45' WHERE `key`='DB_VERSION';