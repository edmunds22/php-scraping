CREATE TABLE `sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `used` tinyint(4) DEFAULT '0',
  `email` varchar(145) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(245) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`,`url`)
) ENGINE=InnoDB AUTO_INCREMENT=251 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;