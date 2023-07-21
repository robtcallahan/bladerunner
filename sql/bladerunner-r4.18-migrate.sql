SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Create the new database: `hpsim`
--
USE `hpsim`;

CREATE TABLE IF NOT EXISTS `snapshot_list` (
  `id` int(10) NOT NULL auto_increment,
  `dateStamp` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `datestamp` (`dateStamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `blade_snapshot` (
  `id` int(10) NOT NULL auto_increment,
  `dateStamp` date NOT NULL default '0000-00-00',
  `deviceName` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `fullDnsName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cmdbName` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `inCmdb` tinyint(1) NOT NULL DEFAULT '0',
  `isInventory` int(1) DEFAULT '0',
  `isSpare` int(1) DEFAULT '0',
  `chassisId` int(10) DEFAULT NULL,
  `serialNumber` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `memorySizeGB` int(5) DEFAULT NULL,
  `numCpus` int(2) DEFAULT NULL,
  `numCoresPerCpu` int(2) DEFAULT NULL,
  `slotNumber` int(2) DEFAULT NULL,
  `distSwitchName` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sysId` char(33) COLLATE utf8_unicode_ci NOT NULL,
  `environment` varchar(632) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cmInstallStatus` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `businessService` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subsystem` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `blade_snapshot_chassisId_fk` (`chassisId`),
  KEY `deviceName` (`deviceName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `vm_snapshot` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `dateStamp` date NOT NULL default '0000-00-00',
  `bladeId` int(10) NOT NULL,
  `isVmware` tinyint(1) NOT NULL DEFAULT '0',
  `deviceName` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `fullDnsName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `inCmdb` tinyint(1) NOT NULL DEFAULT '0',
  `memorySize` int(10) DEFAULT NULL,
  `numberOfCpus` int(2) DEFAULT NULL,
  `sysId` char(33) COLLATE utf8_unicode_ci NOT NULL,
  `environment` varchar(632) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cmInstallStatus` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `businessService` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subsystem` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vm_bladeId_fk` (`bladeId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- -----------------------------------------------------------------------------
