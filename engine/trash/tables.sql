-- -------------------------------------------------------- --
-- DEFAULT MySQL tables structure for Kerno CMS
-- -------------------------------------------------------- --

-- 
-- Table `PREFIX_config`
-- 

CREATE TABLE `XPREFIX_config` (
  `name` char(60),
  `value` char(100),
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table `PREFIX_category`
-- 

CREATE TABLE `XPREFIX_category` (
  `id` int(10) NOT NULL auto_increment,
  `position` int(10) default NULL,
  `name` varchar(50) NOT NULL default '',
  `alt` varchar(50) NOT NULL default '',
  `flags` char(10) default '',
  `tpl` char(20) default '',
  `number` int default 0,
  `parent` int(10) default '0',
  `description` text,
  `keywords` text,
  `info` text,
  `icon` varchar(255) NOT NULL,
  `image_id` int default '0',
  `alt_url` text,
  `orderby` varchar(30) default 'id desc',
  `posts` int default 0,
  `posorder` int default 999,
  `poslevel` int default 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Tabel 'PREFIX_FILES'
-- 

CREATE TABLE `XPREFIX_files` (
  `id` int(11) UNSIGNED NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `orig_name` varchar(100) NOT NULL default '',
  `description` varchar(100) NOT NULL default '',
  `folder` varchar(100) NOT NULL default '',
  `date` int(10) NOT NULL default '0',
  `user` varchar(100) NOT NULL default '',
  `owner_id` int(10) default '0',
  `category` int(10) default '0',
  `linked_ds` int(10) default 0,
  `linked_id` int(10) default 0,
  `plugin` char(30) default '',
  `pidentity` char(30) default '',
  `storage` int(1) default 0,
  PRIMARY KEY  (`id`),
  KEY `link` (`linked_ds`, `linked_id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table 'PREFIX_FLOOD'
-- 

CREATE TABLE `XPREFIX_flood` (
  `ip` VARCHAR(45) NOT NULL default '',
 -- `id` INT(10) DEFAULT NULL,
  `set_date` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY  (`ip`),
  KEY `flood_set_date` (`set_date`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table 'PREFIX_IMAGES'
-- 

CREATE TABLE `XPREFIX_images` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `orig_name` varchar(100) NOT NULL default '',
  `description` varchar(100) NOT NULL default '',
  `folder` varchar(100) NOT NULL default '',
  `date` int(10) NOT NULL default '0',
  `user` varchar(100) NOT NULL default '',
  `width` int(10) default 0,
  `height` int(10) default 0,
  `preview` tinyint(1) default '0',
  `p_width` int(10) default 0,
  `p_height` int(10) default 0,
  `owner_id` int(10) default '0',
  `stamp` int(10) default '0',
  `category` int(10) default '0',
  `linked_ds` int(10) default 0,
  `linked_id` int(10) default 0,
  `plugin` char(30) default '',
  `pidentity` char(30) default '',
  `storage` int(1) default 0,
  PRIMARY KEY  (`id`),
  KEY `link` (`linked_ds`, `linked_id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table 'PREFIX_ipban`
-- 

CREATE TABLE `XPREFIX_ipban` (
  `id` int not null auto_increment,
  `addr` char(32),
  `atype` int default 0,
  `addr_start` bigint default 0,
  `addr_stop` bigint default 0,
  `netlen` int default 0,
  `flags` char(10) default '',
  `createdate` datetime,
  `reason` char(255),
  `hitcount` int default 0,
  PRIMARY KEY  (`id`),
  KEY `ban_start` (`addr_start`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table `PREFIX_news`
-- 

CREATE TABLE `XPREFIX_news` (
  `id` INT(11) UNSIGNED NOT NULL auto_increment,
  `postdate` DATETIME NULL default NULL,
  `author` VARCHAR(100) NOT NULL DEFAULT '',
  `author_id` INT(11) NOT NULL DEFAULT '0',
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `content` TEXT NOT NULL,
  `save_rawcontent` TINYINT(1) DEFAULT '0',
  `alt_name` VARCHAR(255) DEFAULT NULL,
  `mainpage` TINYINT(1) DEFAULT '1',
  `approve` TINYINT(1) DEFAULT '0',
  `views` INT(10) DEFAULT '0',
  `favorite` TINYINT(1) DEFAULT '0',
  `pinned` TINYINT(1) DEFAULT '0',
  `catpinned` TINYINT(1) DEFAULT '0',
  `flags` TINYINT(1) DEFAULT '0',
  `num_files` INT(10) DEFAULT '0',
  `num_images` INT(10) DEFAULT '0',
  `editdate` DATETIME NULL DEFAULT NULL,
  `catid` VARCHAR(255) NOT NULL default '0',
  `description` TEXT NOT NULL,
  `keywords` TEXT NOT NULL,
  `rating` INT(10) NOT NULL DEFAULT '0',
  `votes` INT(10) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  KEY `news_title` (`title`(191)),
  KEY `news_altname` (`alt_name`(30)),
  KEY `news_postdate` (`postdate`),
  KEY `news_editdate` (`editdate`),
  KEY `news_view` (`views`),
  KEY `news_archive` (`favorite`, `approve`),
  KEY `news_main` (`pinned`,`postdate`,`approve`,`mainpage`),
  KEY `news_mainid` (`approve`,`mainpage`,`pinned`,`id`),
  KEY `news_catid` (`approve`,`catpinned`,`id`),
  KEY `news_mainpage` (`approve`,`pinned`,`id`),
  KEY `news_mcount` (`mainpage`,`approve`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table `PREFIX_news_map`
-- 

CREATE TABLE `XPREFIX_news_map` (
  `news_id` INT(11) UNSIGNED DEFAULT NULL,
  `category_id` INT(11) DEFAULT NULL,
  `dt` DATETIME NULL DEFAULT NULL,
  KEY `nm_newsID` (`news_id`),
  KEY `nm_categoryID` (`category_id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Table `PREFIX_news_rawcontent`
--

CREATE TABLE `XPREFIX_news_rawcontent` (
  `news_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `content_raw` TEXT NOT NULL,
  PRIMARY KEY (`news_id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table `PREFIX_static`
-- 

CREATE TABLE `XPREFIX_static` (
  `id` INT(11) NOT NULL auto_increment,
  `postdate` DATETIME NULL DEFAULT NULL,
  `title` VARCHAR(255) default NULL,
  `content` TEXT,
  `alt_name` VARCHAR(255) default '',
  `template` VARCHAR(100) default '',
  `description` TEXT NULL,
  `keywords` TEXT NULL,
  `approve` TINYINT(1) default 0,
  `flags` TINYINT(1) default '0',
  PRIMARY KEY  (`id`),
  KEY `static_title` (`title`),
  KEY `static_altname` (`alt_name`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table `PREFIX_users`
-- 

CREATE TABLE `XPREFIX_users` (
  `id` INT(11) NOT NULL auto_increment,
  `name` VARCHAR(100) NOT NULL default '',
  `pass` VARCHAR(100) NULL DEFAULT NULL,
  `mail` VARCHAR(80) NULL DEFAULT NULL,
  `news` INT(10) DEFAULT '0',
  `status` INT(10) DEFAULT '4',
  `lastenter_date` TIMESTAMP NULL DEFAULT NULL,
  `registration_date` DATETIME NULL DEFAULT NULL,
  `avatar` VARCHAR(100) NOT NULL DEFAULT '',
  `timezone` VARCHAR(50) NULL DEFAULT NULL,
  `activation` VARCHAR(25) NOT NULL DEFAULT '',
  `ip` VARCHAR(45) NOT NULL DEFAULT '0',
  `authcookie` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `users_name` (`name`),
  KEY `users_auth` (`authcookie`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- Table `PREFIX_users_restorepass`
--

CREATE TABLE `XPREFIX_users_restorepass` (
  `id` INT(11) NOT NULL auto_increment,
  `user_id` INT(11) NOT NULL DEFAULT '0',
  `code` CHAR(20) DEFAULT '',
  `request_date` TIMESTAMP NULL default NULL,
  `is_restored` TINYINT(1) DEFAULT 0,
  PRIMARY KEY  (`id`),
  KEY `restorepass_code` (`code`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table `PREFIX_users_pm`
-- 

CREATE TABLE `XPREFIX_users_pm` (
  `pmid` INT(10) NOT NULL auto_increment,
  `from_id` INT(10) DEFAULT '0',
  `to_id` INT(10) DEFAULT '0',
  `pmdate` DATETIME NULL DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `viewed` TINYINT(1) default '0',
  PRIMARY KEY  (`pmid`),
  KEY `from_id` (`from_id`,`to_id`,`viewed`)
) ENGINE=InnoDB;


-- --------------------------------------------------------

-- 
-- Table `PREFIX_load`
-- 

CREATE TABLE `XPREFIX_load` (
  `dt` datetime not null,
  `hit_core` int(11),
  `hit_plugin` int(11),
  `hit_ppage` int(11),
  `exectime` float,
  `exec_core` float,
  `exec_plugin` float,
  `exec_ppage` float,
  PRIMARY KEY (`dt`)
) ENGINE=InnoDB;


-- --------------------------------------------------------

-- 
-- Table `PREFIX_syslog`
-- 

CREATE TABLE `XPREFIX_syslog` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `dt` DATETIME,
  `ip` VARCHAR(45),
  `plugin` CHAR(30),
  `item` CHAR(30),
  `ds` INT(11),
  `ds_id` INT(11),
  `action` CHAR(30),
  `alist` TEXT,
  `userid` INT(11),
  `username` CHAR(30),
  `status` INT(11),
  `stext` CHAR(90),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

-- 
-- Table `PREFIX_profiler`
-- 

CREATE TABLE `XPREFIX_profiler` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `dt` DATETIME NULL DEFAULT NULL,
  `userid` INT(11) NULL DEFAULT NULL,
  `exectime` FLOAT NULL DEFAULT NULL,
  `memusage` FLOAT NULL DEFAULT NULL,
  `url` CHAR(90) NULL DEFAULT NULL,
  `tracedata` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `ondt` (`dt`)
) ENGINE=InnoDB;


-- --------------------------------------------------------

-- 
-- Table `XPREFIX_news_view`
-- 

CREATE TABLE `XPREFIX_news_view` (
	`id` INT(11) UNSIGNED NOT NULL,
	`cnt` INT(11) DEFAULT '0',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;