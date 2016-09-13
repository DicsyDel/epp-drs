

DROP TABLE IF EXISTS `callcodes`;
CREATE TABLE `callcodes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `code` varchar(10) NOT NULL,
  `area_name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `callcodes` (`id`,`code`,`area_name`) VALUES 
 (1,'0',''),
 (2,'1',''),
 (3,'7',''),
 (4,'20',''),
 (5,'27',''),
 (6,'30',''),
 (7,'31',''),
 (8,'32',''),
 (9,'33',''),
 (10,'34',''),
 (11,'36',''),
 (12,'39',''),
 (13,'40',''),
 (14,'41',''),
 (15,'43',''),
 (16,'44',''),
 (17,'45',''),
 (18,'46',''),
 (19,'47',''),
 (20,'48',''),
 (21,'49',''),
 (22,'51',''),
 (23,'52',''),
 (24,'53',''),
 (25,'54',''),
 (26,'55',''),
 (27,'56',''),
 (28,'57',''),
 (29,'58',''),
 (30,'60',''),
 (31,'61',''),
 (32,'62',''),
 (33,'63',''),
 (34,'64',''),
 (35,'65',''),
 (36,'66',''),
 (37,'81',''),
 (38,'82',''),
 (39,'84',''),
 (40,'86',''),
 (41,'90',''),
 (42,'91',''),
 (43,'92',''),
 (44,'93',''),
 (45,'94',''),
 (46,'95',''),
 (47,'201',''),
 (48,'212',''),
 (49,'213',''),
 (50,'216',''),
 (51,'220',''),
 (52,'221',''),
 (53,'222',''),
 (54,'223',''),
 (55,'224',''),
 (56,'225',''),
 (57,'226',''),
 (58,'227',''),
 (59,'228',''),
 (60,'229',''),
 (61,'230',''),
 (62,'231',''),
 (63,'232',''),
 (64,'233',''),
 (65,'234',''),
 (66,'235',''),
 (67,'236',''),
 (68,'237',''),
 (69,'238',''),
 (70,'239',''),
 (71,'240',''),
 (72,'241',''),
 (73,'242',''),
 (74,'243',''),
 (75,'244',''),
 (76,'245',''),
 (77,'247',''),
 (78,'248',''),
 (79,'249',''),
 (80,'250',''),
 (81,'251',''),
 (82,'252',''),
 (83,'253',''),
 (84,'254',''),
 (85,'255',''),
 (86,'256',''),
 (87,'257',''),
 (88,'258',''),
 (89,'260',''),
 (90,'261',''),
 (91,'262',''),
 (92,'264',''),
 (93,'265',''),
 (94,'266',''),
 (95,'267',''),
 (96,'268',''),
 (97,'269',''),
 (98,'290',''),
 (99,'291',''),
 (100,'297',''),
 (101,'298',''),
 (102,'299',''),
 (103,'350',''),
 (104,'351',''),
 (105,'352',''),
 (106,'353',''),
 (107,'354',''),
 (108,'355',''),
 (109,'356',''),
 (110,'357',''),
 (111,'358',''),
 (112,'359',''),
 (113,'370',''),
 (114,'371',''),
 (115,'372',''),
 (116,'373',''),
 (117,'374',''),
 (118,'375',''),
 (119,'376',''),
 (120,'378',''),
 (121,'380',''),
 (122,'381',''),
 (123,'382',''),
 (124,'385',''),
 (125,'386',''),
 (126,'387',''),
 (127,'389',''),
 (128,'420',''),
 (129,'421',''),
 (130,'423',''),
 (131,'500',''),
 (132,'501',''),
 (133,'502',''),
 (134,'503',''),
 (135,'504',''),
 (136,'505',''),
 (137,'506',''),
 (138,'507',''),
 (139,'508',''),
 (140,'509',''),
 (141,'590',''),
 (142,'591',''),
 (143,'592',''),
 (144,'593',''),
 (145,'594',''),
 (146,'595',''),
 (147,'596',''),
 (148,'597',''),
 (149,'598',''),
 (150,'599',''),
 (151,'670',''),
 (152,'672',''),
 (153,'673',''),
 (154,'674',''),
 (155,'675',''),
 (156,'676',''),
 (157,'677',''),
 (158,'678',''),
 (159,'679',''),
 (160,'680',''),
 (161,'681',''),
 (162,'682',''),
 (163,'683',''),
 (164,'684',''),
 (165,'685',''),
 (166,'686',''),
 (167,'687',''),
 (168,'688',''),
 (169,'689',''),
 (170,'690',''),
 (171,'691',''),
 (172,'692',''),
 (173,'809',''),
 (174,'850',''),
 (175,'852',''),
 (176,'853',''),
 (177,'855',''),
 (178,'856',''),
 (179,'872',''),
 (180,'880',''),
 (181,'886',''),
 (182,'960',''),
 (183,'961',''),
 (184,'962',''),
 (185,'964',''),
 (186,'965',''),
 (187,'966',''),
 (188,'967',''),
 (189,'968',''),
 (190,'971',''),
 (191,'972',''),
 (192,'973',''),
 (193,'974',''),
 (194,'975',''),
 (195,'976',''),
 (196,'977',''),
 (197,'993',''),
 (198,'994',''),
 (199,'995',''),
 (200,'998','');

--
-- Definition of table `client_fields`
--

DROP TABLE IF EXISTS `client_fields`;
CREATE TABLE `client_fields` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `type` enum('TEXT','BOOL','SELECT') default 'TEXT',
  `title` varchar(255) default NULL,
  `required` tinyint(1) default '0',
  `defval` varchar(255) default NULL,
  `elements` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Definition of table `client_info`
--

DROP TABLE IF EXISTS `client_info`;
CREATE TABLE `client_info` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `fieldid` int(11) default NULL,
  `value` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Definition of table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `id` int(11) NOT NULL auto_increment,
  `key` varchar(255) NOT NULL default '',
  `value` varchar(255) character set utf8 NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`id`,`key`,`value`) VALUES 
(1,'pass','8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918'),
(2,'menustyle','0'),
(3,'user_prefix','u'),
(4,'paging_items','20'),
(7,'login','admin'),
(8,'email_dsn',''),
(9,'email_copy','1'),
(10,'email_admin','root@localhost'),
(11,'email_adminname','EPP-DRS Service owner'),
(12,'support_email','root@localhost'),
(13,'support_name','EPP-DRS Support'),
(14,'company_name','EPP-DRS Service'),
(15,'site_url','demo.epp-drs.com'),
(16,'currency','&euro;'),
(17,'currencyISO','EUR'),
(18,'ns1','ns.hostdad.com'),
(19,'ns2','ns2.hostdad.com'),
(420,'allow_A_record','1'),
(421,'allow_MX_record','1'),
(422,'allow_NS_record','1'),
(423,'allow_CNAME_record','1'),
(424,'rotate_log_every','7'),
(426,'zendid_path',''),
(427,'inline_help','1'),
(429,'phone_format','+[cc]-[2-4]-[4-10]'),
(430,'billing_currencyISO','EUR'),
(431,'currency_rate','1'),
(432,'user_vat','0'),
(433,'auto_delete','0'),
(434,'invoice_customid_format','%id%');

--
-- Definition of table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
CREATE TABLE  `contacts` (
  `id` int(11) NOT NULL auto_increment,
  `clid` varchar(38) NOT NULL default '',
  `TLD` varchar(20) NOT NULL default '',
  `userid` int(11) NOT NULL default '0',
  `fullname` VARCHAR(255)  NOT NULL,
  `pw` varchar(255) NOT NULL default '',
  `status` tinyint(1) NOT NULL default '0',
  `parent_clid` varchar(16) default NULL,
  `groupname` varchar(16) default NULL,
  `strict_fields` tinyint(1) unsigned NOT NULL default '1',
  `module_name` varchar(32) default NULL,
  `section_name` varchar(32) NOT NULL,
  `target_index` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Definition of table `contacts_data`
--

DROP TABLE IF EXISTS `contacts_data`;
CREATE TABLE `contacts_data` (
  `id` int(11) NOT NULL auto_increment,
  `contactid` varchar(255) default NULL,
  `field` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `contactid` (`contactid`,`field`),
  INDEX `value`(`value`(255))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `contacts_discloses`
--

DROP TABLE IF EXISTS `contacts_discloses`;
CREATE TABLE `contacts_discloses` (
  `id` int(11) NOT NULL auto_increment,
  `contactid` varchar(255) default NULL,
  `field_name` varchar(255) default NULL,
  `value` tinyint(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `contactid` (`contactid`,`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `contacts_discloses`
--

--
-- Definition of table `countries`
--

DROP TABLE IF EXISTS `countries`;
CREATE TABLE `countries` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `code` char(2) NOT NULL default '',
  `enabled` tinyint(1) default '1',
  `vat` int(2) default '0',
  PRIMARY KEY  (`id`),
  KEY `IDX_COUNTRIES_NAME` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `countries`
--

/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` (`id`,`name`,`code`,`enabled`,`vat`) VALUES 
 (1,'Afghanistan','AF',1,0),
 (2,'Albania','AL',1,0),
 (3,'Algeria','DZ',1,0),
 (4,'American Samoa','AS',1,0),
 (5,'Andorra','AD',1,0),
 (6,'Angola','AO',1,0),
 (7,'Anguilla','AI',1,0),
 (8,'Antarctica','AQ',1,0),
 (9,'Antigua and Barbuda','AG',1,0),
 (10,'Argentina','AR',1,0),
 (11,'Armenia','AM',1,0),
 (12,'Aruba','AW',1,0),
 (13,'Australia','AU',1,0),
 (14,'Austria','AT',1,0),
 (15,'Azerbaijan','AZ',1,0),
 (16,'Bahamas','BS',1,0),
 (17,'Bahrain','BH',1,0),
 (18,'Bangladesh','BD',1,0),
 (19,'Barbados','BB',1,0),
 (20,'Belarus','BY',1,0),
 (21,'Belgium','BE',1,0),
 (22,'Belize','BZ',1,0),
 (23,'Benin','BJ',1,0),
 (24,'Bermuda','BM',1,0),
 (25,'Bhutan','BT',1,0),
 (26,'Bolivia','BO',1,0),
 (27,'Bosnia and Herzegowina','BA',1,0),
 (28,'Botswana','BW',1,0),
 (29,'Bouvet Island','BV',1,0),
 (30,'Brazil','BR',1,0),
 (31,'British Indian Ocean Territory','IO',1,0),
 (32,'Brunei Darussalam','BN',1,0),
 (33,'Bulgaria','BG',1,0),
 (34,'Burkina Faso','BF',1,0),
 (35,'Burundi','BI',1,0),
 (36,'Cambodia','KH',1,0),
 (37,'Cameroon','CM',1,0),
 (38,'Canada','CA',1,0),
 (39,'Cape Verde','CV',1,0),
 (40,'Cayman Islands','KY',1,0),
 (41,'Central African Republic','CF',1,0),
 (42,'Chad','TD',1,0),
 (43,'Chile','CL',1,0),
 (44,'China','CN',1,0),
 (45,'Christmas Island','CX',1,0),
 (46,'Cocos (Keeling) Islands','CC',1,0),
 (47,'Colombia','CO',1,0),
 (48,'Comoros','KM',1,0),
 (49,'Congo','CG',1,0),
 (50,'Cook Islands','CK',1,0),
 (51,'Costa Rica','CR',1,0),
 (52,'Cote D\'Ivoire','CI',1,0),
 (53,'Croatia','HR',1,0),
 (54,'Cuba','CU',1,0),
 (55,'Cyprus','CY',1,0),
 (56,'Czech Republic','CZ',1,0),
 (57,'Denmark','DK',1,0),
 (58,'Djibouti','DJ',1,0),
 (59,'Dominica','DM',1,0),
 (60,'Dominican Republic','DO',1,0),
 (61,'East Timor','TP',1,0),
 (62,'Ecuador','EC',1,0),
 (63,'Egypt','EG',1,0),
 (64,'El Salvador','SV',1,0),
 (65,'Equatorial Guinea','GQ',1,0),
 (66,'Eritrea','ER',1,0),
 (67,'Estonia','EE',1,0),
 (68,'Ethiopia','ET',1,0),
 (69,'Falkland Islands (Malvinas)','FK',1,0),
 (70,'Faroe Islands','FO',1,0),
 (71,'Fiji','FJ',1,0),
 (72,'Finland','FI',1,0),
 (73,'France','FR',1,0),
 (74,'France, MEtropolitan','FX',1,0),
 (75,'French Guiana','GF',1,0),
 (76,'French Polynesia','PF',1,0),
 (77,'French Southern Territories','TF',1,0),
 (78,'Gabon','GA',1,0),
 (79,'Gambia','GM',1,0),
 (80,'Georgia','GE',1,0),
 (81,'Germany','DE',1,0),
 (82,'Ghana','GH',1,0),
 (83,'Gibraltar','GI',1,0),
 (84,'Greece','GR',1,0),
 (85,'Greenland','GL',1,0),
 (86,'Grenada','GD',1,0),
 (87,'Guadeloupe','GP',1,0),
 (88,'Guam','GU',1,0),
 (89,'Guatemala','GT',1,0),
 (90,'Guinea','GN',1,0),
 (91,'Guinea-bissau','GW',1,0),
 (92,'Guyana','GY',1,0),
 (93,'Haiti','HT',1,0),
 (94,'Heard and Mc Donald Islands','HM',1,0),
 (95,'Honduras','HN',1,0),
 (96,'Hong Kong','HK',1,0),
 (97,'Hungary','HU',1,0),
 (98,'Iceland','IS',1,0),
 (99,'India','IN',1,0),
 (100,'Indonesia','ID',1,0),
 (101,'Iran (Islamic Republic of)','IR',1,0),
 (102,'Iraq','IQ',1,0),
 (103,'Ireland','IE',1,0),
 (104,'Israel','IL',1,0),
 (105,'Italy','IT',1,0),
 (106,'Jamaica','JM',1,0),
 (107,'Japan','JP',1,0),
 (108,'Jordan','JO',1,0),
 (109,'Kazakhstan','KZ',1,0),
 (110,'Kenya','KE',1,0),
 (111,'Kiribati','KI',1,0),
 (112,'Korea, Democratic People\'s Republic of','KP',1,0),
 (113,'Korea, Republic of','KR',1,0),
 (114,'Kuwait','KW',1,0),
 (115,'Kyrgyzstan','KG',1,0),
 (116,'Lao People\'s Democratic Republic','LA',1,0),
 (117,'Latvia','LV',1,0),
 (118,'Lebanon','LB',1,0),
 (119,'Lesotho','LS',1,0),
 (120,'Liberia','LR',1,0),
 (121,'Libyan Arab Jamahiriya','LY',1,0),
 (122,'Liechtenstein','LI',1,0),
 (123,'Lithuania','LT',1,0),
 (124,'Luxembourg','LU',1,0),
 (125,'Macau','MO',1,0),
 (126,'Macedonia, The Former Yugoslav Republic of','MK',1,0),
 (127,'Madagascar','MG',1,0),
 (128,'Malawi','MW',1,0),
 (129,'Malaysia','MY',1,0),
 (130,'Maldives','MV',1,0),
 (131,'Mali','ML',1,0),
 (132,'Malta','MT',1,0),
 (133,'Marshall Islands','MH',1,0),
 (134,'Martinique','MQ',1,0),
 (135,'Mauritania','MR',1,0),
 (136,'Mauritius','MU',1,0),
 (137,'Mayotte','YT',1,0),
 (138,'Mexico','MX',1,0),
 (139,'Micronesia, Federated States of','FM',1,0),
 (140,'Moldova, Republic of','MD',1,0),
 (141,'Monaco','MC',1,0),
 (142,'Mongolia','MN',1,0),
 (143,'Montserrat','MS',1,0),
 (144,'Morocco','MA',1,0),
 (145,'Mozambique','MZ',1,0),
 (146,'Myanmar','MM',1,0),
 (147,'Namibia','NA',1,0),
 (148,'Nauru','NR',1,0),
 (149,'Nepal','NP',1,0),
 (150,'Netherlands','NL',1,0),
 (151,'Netherlands Antilles','AN',1,0),
 (152,'New Caledonia','NC',1,0),
 (153,'New Zealand','NZ',1,0),
 (154,'Nicaragua','NI',1,0),
 (155,'Niger','NE',1,0),
 (156,'Nigeria','NG',1,0),
 (157,'Niue','NU',1,0),
 (158,'Norfolk Island','NF',1,0),
 (159,'Northern Mariana Islands','MP',1,0),
 (160,'Norway','NO',1,0),
 (161,'Oman','OM',1,0),
 (162,'Pakistan','PK',1,0),
 (163,'Palau','PW',1,0),
 (164,'Panama','PA',1,0),
 (165,'Papua New Guinea','PG',1,0),
 (166,'Paraguay','PY',1,0),
 (167,'Peru','PE',1,0),
 (168,'Philippines','PH',1,0),
 (169,'Pitcairn','PN',1,0),
 (170,'Poland','PL',1,0),
 (171,'Portugal','PT',1,0),
 (172,'Puerto Rico','PR',1,0),
 (173,'Qatar','QA',1,0),
 (174,'Reunion','RE',1,0),
 (175,'Romania','RO',1,0),
 (176,'Russian Federation','RU',1,0),
 (177,'Rwanda','RW',1,0),
 (178,'Saint Kitts and Nevis','KN',1,0),
 (179,'Saint Lucia','LC',1,0),
 (180,'Saint Vincent and the Grenadines','VC',1,0),
 (181,'Samoa','WS',1,0),
 (182,'San Marino','SM',1,0),
 (183,'Sao Tome and Principe','ST',1,0),
 (184,'Saudi Arabia','SA',1,0),
 (185,'Senegal','SN',1,0),
 (186,'Seychelles','SC',1,0),
 (187,'Sierra Leone','SL',1,0),
 (188,'Singapore','SG',1,0),
 (189,'Slovakia (Slovak Republic)','SK',1,0),
 (190,'Slovenia','SI',1,0),
 (191,'Solomon Islands','SB',1,0),
 (192,'Somalia','SO',1,0),
 (193,'South Africa','ZA',1,0),
 (194,'South Georgia and the South Sandwich Islands','GS',1,0),
 (195,'Spain','ES',1,0),
 (196,'Sri Lanka','LK',1,0),
 (197,'St. Helena','SH',1,0),
 (198,'St. Pierre and Miquelon','PM',1,0),
 (199,'Sudan','SD',1,0),
 (200,'Suriname','SR',1,0),
 (201,'Svalbard and Jan Mayen Islands','SJ',1,0),
 (202,'Swaziland','SZ',1,0),
 (203,'Sweden','SE',1,0),
 (204,'Switzerland','CH',1,0),
 (205,'Syrian Arab Republic','SY',1,0),
 (206,'Taiwan, Province of China','TW',1,0),
 (207,'Tajikistan','TJ',1,0),
 (208,'Tanzania, United Republic of','TZ',1,0),
 (209,'Thailand','TH',1,0),
 (210,'Togo','TG',1,0),
 (211,'Tokelau','TK',1,0),
 (212,'Tonga','TO',1,0),
 (213,'Trinidad and Tobago','TT',1,0),
 (214,'Tunisia','TN',1,0),
 (215,'Turkey','TR',1,0),
 (216,'Turkmenistan','TM',1,0),
 (217,'Turks and Caicos Islands','TC',1,0),
 (218,'Tuvalu','TV',1,0),
 (219,'Uganda','UG',1,0),
 (220,'Ukraine','UA',1,30),
 (221,'United Arab Emirates','AE',1,0),
 (222,'United Kingdom','GB',1,0),
 (223,'United States','US',1,0),
 (224,'United States Minor Outlying Islands','UM',1,0),
 (225,'Uruguay','UY',1,0),
 (226,'Uzbekistan','UZ',1,0),
 (227,'Vanuatu','VU',1,0),
 (228,'Vatican City State (Holy See)','VA',1,0),
 (229,'Venezuela','VE',1,0),
 (230,'Viet Nam','VN',1,0),
 (231,'Virgin Islands (British)','VG',1,0),
 (232,'Virgin Islands (U.S.)','VI',1,0),
 (233,'Wallis and Futuna Islands','WF',1,0),
 (234,'Western Sahara','EH',1,0),
 (235,'Yemen','YE',1,0),
 (236,'Yugoslavia','YU',1,0),
 (237,'Zaire','ZR',1,0),
 (238,'Zambia','ZM',1,0),
 (239,'Zimbabwe','ZW',1,0);
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;


--
-- Definition of table `discounts`
--

DROP TABLE IF EXISTS `discounts`;
CREATE TABLE `discounts` (
  `id` int(11) NOT NULL auto_increment,
  `packageid` int(11) default NULL,
  `TLD` varchar(255) default NULL,
  `purpose` varchar(255) default NULL,
  `discount` int(11) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `TLD` (`packageid`,`TLD`,`purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `domains`
--

DROP TABLE IF EXISTS `domains`;
CREATE TABLE `domains` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `TLD` varchar(20) NOT NULL default '',
  `c_registrant` varchar(255) NOT NULL,
  `c_admin` varchar(255) default NULL,
  `c_billing` varchar(255) default NULL,
  `c_tech` varchar(255) default NULL,
  `ns1` varchar(255) default NULL,
  `ns2` varchar(255) default NULL,
  `ns_n` text NOT NULL,
  `status` varchar(50) NOT NULL default 'Awaiting payment',
  `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `period` int(11) NOT NULL default '0',
  `error_msg` text NOT NULL,
  `pw` varchar(255) NOT NULL default '',
  `dtTransfer` datetime NOT NULL default '0000-00-00 00:00:00',
  `protocol` varchar(50) NOT NULL default '',
  `comment` text NOT NULL,
  `islocked` tinyint(1) default '0',
  `sys_status` text,
  `incomplete_operation` varchar(255) default 'Register',
  `managed_dns` tinyint(1) default '0',
  `delete_status` INT(1) NOT NULL DEFAULT 0,
  `renew_disabled` INT(1)  NOT NULL DEFAULT 0, 
  `outgoing_transfer_status` VARCHAR(50),
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `domains_data`
--

DROP TABLE IF EXISTS `domains_data`;
CREATE TABLE `domains_data` (
  `id` int(11) NOT NULL auto_increment,
  `domainid` int(11) default NULL,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `domains_flags`
--

DROP TABLE IF EXISTS `domains_flags`;
CREATE TABLE `domains_flags` (
  `id` int(11) NOT NULL auto_increment,
  `domainid` int(11) default NULL,
  `flag` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `eventhandlers`
--

DROP TABLE IF EXISTS `eventhandlers`;
CREATE TABLE `eventhandlers` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `interface` varchar(255) default NULL,
  `enabled` tinyint(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`name`,`interface`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `eventhandlers_config`
--

DROP TABLE IF EXISTS `eventhandlers_config`;
CREATE TABLE `eventhandlers_config` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) default NULL,
  `key` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  `handler_name` varchar(255) default NULL,
  `type` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `extensions`
--

DROP TABLE IF EXISTS `extensions`;
CREATE TABLE `extensions` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `enabled` tinyint(1) default '0',
  `license_flag` varchar(255) default NULL,
  `key` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `extensions`
--

INSERT INTO `extensions` (`id`,`name`,`description`,`enabled`,`license_flag`,`key`) VALUES 
 (1,'Verisign dropcatching','Verisign pre-registration (dropped domains catching).',1,'EXT_VERISIGN_PREREGISTRATION_DROPCATCHING','PREREGISTRATION'),
 (2,'Managed DNS','Managed DNS',0,'EXT_MANAGED_DNS','MANAGED_DNS');

--
-- Definition of table `invoice_purposes`
--

DROP TABLE IF EXISTS `invoice_purposes`;
CREATE TABLE `invoice_purposes` (
  `id` int(11) NOT NULL auto_increment,
  `key` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `issystem` tinyint(1) default '0',
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `invoice_purposes`
--

INSERT INTO `invoice_purposes` (`id`,`key`,`description`,`issystem`,`name`) VALUES 
 (1,'Domain_Create','Create new domain name',1,'Domain create'),
 (2,'Domain_Renew','Renew domain name',1,'Domain renew'),
 (3,'Domain_Transfer','Transfer domain name',1,'Domain transfer'),
 (4,'Domain_Trade','Trade domain',1,'Domain trade'),
 (5,'Custom','Invoices issues from Registrar CP',1,'Custom invoice'),
 (6,'Preregistration_Dropcatching','Verisign pre-registration (dropped domains catching)',1,'Verisign Preregistration Dropcatching'),
 (7,'Balance_Deposit','Add funds to balance','1','Balance deposit');


--
-- Definition of table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) default NULL,
  `orderid` int(11) default NULL,
  `customid` VARCHAR(80)  NOT NULL DEFAULT '',  
  `purpose` varchar(255) default NULL,
  `total` float default NULL,
  `dtcreated` datetime default NULL,
  `status` tinyint(1) default '0',
  `hidden` INT(1)  DEFAULT 0,
  `cancellable` INT(1)  DEFAULT 0,
  `description` varchar(255) default NULL,
  `itemid` int(11) default NULL,
  `payment_module` varchar(255) default NULL,
  `dtupdated` datetime default NULL,
  `vat` float default NULL,
  `notes` text,
  `action_status` tinyint(1) unsigned default '0',
  `action_fail_reason` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `languages`
--

DROP TABLE IF EXISTS `languages`;
CREATE TABLE `languages` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(10) default NULL,
  `isdefault` tinyint(1) default '0',
  `isinstalled` tinyint(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`,`name`,`isdefault`,`isinstalled`) VALUES 
 (1,'en_US',1,1);

--
-- Definition of table `modules`
--

DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `status` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `modules_config`
--

DROP TABLE IF EXISTS `modules_config`;
CREATE TABLE `modules_config` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `type` ENUM('text','checkbox', 'textarea') NOT NULL default 'text',
  `key` varchar(255) NOT NULL default '',
  `value` text NOT NULL,
  `module_name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `modules_config`
--

--
-- Definition of table `nameservers`
--

DROP TABLE IF EXISTS `nameservers`;
CREATE TABLE `nameservers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `host` varchar(100) default NULL,
  `port` int(10) unsigned default NULL,
  `username` varchar(100) default NULL,
  `password` text,
  `rndc_path` varchar(255) default NULL,
  `named_path` varchar(255) default NULL,
  `namedconf_path` varchar(255) default NULL,
  `isnew` tinyint(1) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Definition of table `nhosts`
--

DROP TABLE IF EXISTS `nhosts`;
CREATE TABLE `nhosts` (
  `id` int(11) NOT NULL auto_increment,
  `domainid` int(11) NOT NULL default '0',
  `hostname` varchar(255) NOT NULL default '',
  `ipaddr` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Definition of table `objects_history`
--

DROP TABLE IF EXISTS `objects_history`;
CREATE TABLE `objects_history` (
  `id` int(11) NOT NULL auto_increment,
  `type` enum('DOMAIN','CONTACT','HOST') default NULL,
  `object` varchar(255) default NULL,
  `operation` varchar(50) default NULL,
  `state` tinyint(1) default '1',
  `before_update` text,
  `after_update` text,
  `transaction_id` varchar(255) default NULL,
  `dtadded` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) unsigned zerofill NOT NULL auto_increment,
  `userid` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `packages`
--

DROP TABLE IF EXISTS `packages`;
CREATE TABLE `packages` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `min_domains` INTEGER,
  `min_balance` FLOAT,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `pending_operations`
--

DROP TABLE IF EXISTS `pending_operations`;
CREATE TABLE `pending_operations` (
  `id` int(11) NOT NULL auto_increment,
  `registry_opid` varchar(45),
  `objectid` varchar(45) default NULL,
  `dtbegin` datetime default NULL,
  `operation` ENUM('CREATE','UPDATE','DELETE','TRADE','TRANSFER', 'CREATE_APPROVE', 'UPDATE_APPROVE') default NULL,
  `objecttype` enum('DOMAIN','CONTACT','NAMESERVERHOST') default NULL,
  `object_before` text,
  `object_after` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Definition of table `pmodules`
--

DROP TABLE IF EXISTS `pmodules`;
CREATE TABLE `pmodules` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `status` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `pmodules_config`
--

DROP TABLE IF EXISTS `pmodules_config`;
CREATE TABLE `pmodules_config` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `type` ENUM('text','checkbox','select') NOT NULL DEFAULT 'text',
  `key` varchar(255) NOT NULL default '',
  `value` text NOT NULL,
  `module_name` varchar(255) NOT NULL default '',
  `hint` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `key` (`key`,`module_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `prices`
--

DROP TABLE IF EXISTS `prices`;
CREATE TABLE `prices` (
  `id` int(11) NOT NULL auto_increment,
  `purpose` varchar(255) NOT NULL,
  `cost` float NOT NULL default '0',
  `TLD` varchar(255) NOT NULL default '',
  `period` int(11) default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `TLD` (`purpose`,`TLD`,`period`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `records`
--

DROP TABLE IF EXISTS `records`;
CREATE TABLE `records` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zoneid` int(10) unsigned NOT NULL default '0',
  `rtype` enum('A','MX','CNAME','NS') default NULL,
  `ttl` int(10) unsigned default NULL,
  `rpriority` int(10) unsigned default NULL,
  `rvalue` varchar(255) default NULL,
  `rkey` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `records`
--

/*!40000 ALTER TABLE `records` DISABLE KEYS */;
/*!40000 ALTER TABLE `records` ENABLE KEYS */;


--
-- Definition of table `states`
--

DROP TABLE IF EXISTS `states`;
CREATE TABLE `states` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `code` varchar(2) default NULL,
  `cc` varchar(2) default 'US',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`,`name`,`code`,`cc`) VALUES 
 (1,'Armed Forces Americas','AA','US'),
 (2,'Armed Forces Europe','AE','US'),
 (3,'Alaska','AK','US'),
 (4,'Alabama','AL','US'),
 (5,'Armed Forces Pacific','AP','US'),
 (6,'Arkansas','AR','US'),
 (7,'American Samoa','AS','US'),
 (8,'Arizona','AZ','US'),
 (9,'California','CA','US'),
 (10,'Colorado','CO','US'),
 (11,'Connecticut','CT','US'),
 (12,'District of Columbia','DC','US'),
 (13,'Delaware','DE','US'),
 (14,'Florida','FL','US'),
 (15,'Federated States of Micronesia','FM','US'),
 (16,'Georgia','GA','US'),
 (17,'Guam','GU','US'),
 (18,'Hawaii','HI','US'),
 (19,'Iowa','IA','US'),
 (20,'Idaho','ID','US'),
 (21,'Illinois','IL','US'),
 (22,'Indiana','IN','US'),
 (23,'Kansas','KS','US'),
 (24,'Kentucky','KY','US'),
 (25,'Louisiana','LA','US'),
 (26,'Massachusetts','MA','US'),
 (27,'Maryland','MD','US'),
 (28,'Maine','ME','US'),
 (29,'Marshall Islands','MH','US'),
 (30,'Michigan','MI','US'),
 (31,'Minnesota','MN','US'),
 (32,'Missouri','MO','US'),
 (33,'Northern Mariana Islands','MP','US'),
 (34,'Mississippi','MS','US'),
 (35,'Montana','MT','US'),
 (36,'North Carolina','NC','US'),
 (37,'North Dakota','ND','US'),
 (38,'Nebraska','NE','US'),
 (39,'New Hampshire','NH','US'),
 (40,'New Jersey','NJ','US'),
 (41,'New Mexico','NM','US'),
 (42,'Nevada','NV','US'),
 (43,'New York','NY','US'),
 (44,'Ohio','OH','US'),
 (45,'Oklahoma','OK','US'),
 (46,'Oregon','OR','US'),
 (47,'Pennsylvania','PA','US'),
 (48,'Puerto Rico','PR','US'),
 (49,'Palau','PW','US'),
 (50,'Rhode Island','RI','US'),
 (51,'South Carolina','SC','US'),
 (52,'South Dakota','SD','US'),
 (53,'Tennessee','TN','US'),
 (54,'Texas','TX','US'),
 (55,'Utah','UT','US'),
 (56,'Virginia','VA','US'),
 (57,'Virgin Islands','VI','US'),
 (58,'Vermont','VT','US'),
 (59,'Washington','WA','US'),
 (60,'West Virginia','WV','US'),
 (61,'Wisconsin','WI','US'),
 (62,'Wyoming','WY','US'),
 (63,'Alberta','AB','CA'),
 (64,'British Columbia','BC','CA'),
 (65,'Manitoba','MB','CA'),
 (66,'New Brunswick','NB','CA'),
 (67,'Newfoundland','NF','CA'),
 (68,'Nova Scotia','NS','CA'),
 (70,'Ontario','ON','CA'),
 (71,'Prince Edward Island','PE','CA'),
 (72,'Quebec','QC','CA'),
 (73,'Saskatchewan','SK','CA'),
 (74,'Northwest Territories','NT','CA'),
 (75,'Yukon Territory','YT','CA'),
 (76,'Eastern Cape','EC','ZA'),
 (77,'Free State','FS','ZA'),
 (78,'Gauteng','GT','ZA'),
 (79,'KwaZulu-Natal','NL','ZA'),
 (80,'Limpopo','LP','ZA'),
 (81,'Mpumalanga','MP','ZA'),
 (82,'Northern Cape','NC','ZA'),
 (83,'North West','NW','ZA'),
 (84,'Western Cape','WC','ZA');


--
-- Definition of table `syslog`
--

DROP TABLE IF EXISTS `syslog`;
CREATE TABLE `syslog` (
  `id` int(11) NOT NULL auto_increment,
  `severity` int(2) default NULL,
  `message` text,
  `dtadded` datetime default NULL,
  `useragent` varchar(255) default NULL,
  `ipaddr` varchar(15) default NULL,
  `transactionid` varchar(255) default NULL,
  `dtadded_time` bigint(20) default NULL,
  `backtrace` text,
  PRIMARY KEY  (`id`),
  KEY `ix_transactionid` (`transactionid`),
  KEY `ix_dtadded_time` (`dtadded_time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Definition of table `tlds`
--

DROP TABLE IF EXISTS `tlds`;
CREATE TABLE `tlds` (
  `id` int(11) NOT NULL auto_increment,
  `TLD` varchar(20) default NULL,
  `isactive` tinyint(1) default '1',
  `modulename` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `TLD` (`TLD`,`modulename`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `updatelog`
--

DROP TABLE IF EXISTS `updatelog`;
CREATE TABLE `updatelog` (
  `id` int(11) NOT NULL auto_increment,
  `dtupdate` datetime default NULL,
  `status` enum('Success','Failed') default NULL,
  `report` text,
  `transactionid` varchar(255) default NULL,
  `from_revision` int(11) default NULL,
  `to_revision` int(11) default NULL,
  `email_status` ENUM('Send', 'Await', 'Failed'),
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) default NULL,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_settings`
--

--
-- Definition of table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(50) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `status` tinyint(1) default '1',
  `dtregistered` int(11) NOT NULL default '0',
  `packageid` int(11) default '0',
  `package_fixed` INT(1) DEFAULT 0,
  `name` varchar(255) default NULL,
  `org` varchar(255) default NULL,
  `business` varchar(255) default NULL,
  `address` varchar(255) default NULL,
  `address2` varchar(255) default NULL,
  `city` varchar(255) default NULL,
  `state` varchar(255) default NULL,
  `country` varchar(255) default NULL,
  `zipcode` varchar(255) default NULL,
  `phone` varchar(255) default NULL,
  `fax` varchar(255) default NULL,
  `nemail` varchar(255) default NULL,
  `vat` INT(2)  NOT NULL DEFAULT -1,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Definition of table `zones`
--

DROP TABLE IF EXISTS `zones`;
CREATE TABLE `zones` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zone` varchar(255) default NULL,
  `soa_owner` varchar(100) default NULL,
  `soa_ttl` int(10) unsigned default NULL,
  `soa_parent` varchar(100) default NULL,
  `soa_serial` int(10) unsigned default NULL,
  `soa_refresh` int(10) unsigned default NULL,
  `soa_retry` int(10) unsigned default NULL,
  `soa_expire` int(10) unsigned default NULL,
  `min_ttl` int(10) unsigned default NULL,
  `isupdated` tinyint(1) default '0',
  `isdeleted` tinyint(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `zones_index3945` (`zone`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `task_queue` (
  `id` int(11) NOT NULL auto_increment,
  `job_classname` varchar(255) NOT NULL,
  `dtadded` datetime NOT NULL,
  `job_object` text NOT NULL,
  `userid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `task_target` (
  `id` int(11) NOT NULL auto_increment,
  `taskid` int(11) NOT NULL,
  `target` varchar(255) NOT NULL,
  `fail_count` int(1) NOT NULL default '0',
  `fail_reason` TEXT default NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE  `balance` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) NOT NULL,
  `total` double NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `balance_history` (
  `id` int(11) NOT NULL auto_increment,
  `balanceid` int(11) NOT NULL,
  `invoiceid` int(11) NOT NULL,
  `amount` double NOT NULL,
  `operation_date` datetime NOT NULL,
  `operation_type` enum('Deposit','Withdraw') NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `api_log` (
  `id` INTEGER  NOT NULL AUTO_INCREMENT,
  `transaction_id` VARCHAR(36)  NOT NULL,
  `added_date` DATETIME  NOT NULL,
  `action` VARCHAR(100)  NOT NULL,
  `ipaddress` VARCHAR(15)  NOT NULL,
  `request` TEXT  NOT NULL,
  `response` TEXT  NOT NULL,
  `error_trace` text NOT NULL,  
  `user_id` INTEGER  NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
)
ENGINE = MyISAM DEFAULT CHARSET=latin1;
