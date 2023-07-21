-- -----------------------------------------------------------------------------
--
-- $Id: bladerunner-r1.sql 66767 2012-08-09 02:19:00Z rcallaha $
-- $Date: 2012-08-08 22:19:00 -0400 (Wed, 08 Aug 2012) $
-- $Author: rcallaha $
-- $Revision: 66767 $
-- $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/sql/bladerunner-r1.sql $
--
-- -----------------------------------------------------------------------------

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `bladerunner`
--
DROP DATABASE IF EXISTS `bladerunner`;
CREATE DATABASE `bladerunner` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `bladerunner`;

-- -----------------------------------------------------------------------------

--
-- Table structure for table `chassis`
--                                                                                                     
DROP TABLE IF EXISTS `chassis`;
CREATE TABLE IF NOT EXISTS `chassis` (
  `id` int(10) NOT NULL,
  `fullDnsName` varchar(64) NULL,

  `deviceName` varchar(32) NOT NULL,
  `hwStatus` varchar(32) NULL,
  `mpStatus` varchar(32) NULL,
  `deviceType` varchar(32) NULL,
  `productName` varchar(64) NULL,
  `assocDeviceName` varchar(32) NULL,
  `assocDeviceType` varchar(32) NULL,
  `assocDeviceKey` int(10) NULL,
  `assocType` varchar(32) NULL,
  
  `distSwitchName` varchar(40) NULL,

  `sysId` char(33) NOT NULL,
  `environment` varchar(632) default NULL,
  `cmHwStatus` varchar(32) default NULL,
  `businessService` varchar(64) default NULL,
  `subsystem` varchar(64) default NULL,
  `opsSuppMgr` varchar(64) default NULL,
  `opsSuppGrp` varchar(64) default NULL,

  PRIMARY KEY `id` (`id`),
  UNIQUE KEY `deviceName` (`deviceName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  
-- -----------------------------------------------------------------------------

--
-- Table structure for table `chassis_wwn`
--
DROP TABLE IF EXISTS `chassis_wwn`;
CREATE TABLE IF NOT EXISTS `chassis_wwn` (
  `id` int(10) NOT NULL auto_increment,
  `chassisId` int(10) NOT NULL,
  `wwn` varchar(24) NOT NULL,
  `type` varchar(10) NULL,
  `usedBy` varchar(20) NULL,
  `speed` varchar(16) NULL,
  `status` varchar(16),

  PRIMARY KEY `id` (`id`),
  KEY `chassisId` (`chassisId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  
-- -----------------------------------------------------------------------------

--
-- Table structure for table `mgmt_processor`
--
DROP TABLE IF EXISTS `mgmt_processor`;
CREATE TABLE IF NOT EXISTS `mgmt_processor` (
  `id` int(10) NOT NULL,
  `deviceName` varchar(32) NOT NULL,
  `fullDnsName` varchar(64) NULL,

  `hwStatus` varchar(32) NULL,
  `mpStatus` varchar(32) NULL,
  `deviceType` varchar(32) NULL,
  `deviceAddress` varchar(16) NULL,
  `productName` varchar(64) NULL,
  `osName` varchar(64) NULL,

  `assocDeviceName` varchar(32) NULL,
  `assocDeviceType` varchar(32) NULL,
  `chassisId` int(10) NULL,
  `assocType` varchar(32) NULL,

  `version` varchar(10) NULL,
  
  PRIMARY KEY `id` (`id`),
  UNIQUE KEY `deviceName` (`deviceName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  
-- -----------------------------------------------------------------------------

--
-- Table structure for table `switch`
--
DROP TABLE IF EXISTS `switch`;
CREATE TABLE IF NOT EXISTS `switch` (
  `id` int(10) NOT NULL,
  `deviceName` varchar(32) NOT NULL,
  `fullDnsName` varchar(64) NULL,

  `hwStatus` varchar(32) NULL,
  `mpStatus` varchar(32) NULL,
  `swStatus` varchar(32) NULL,
  `deviceType` varchar(32) NULL,
  `deviceAddress` varchar(16) NULL,
  `productName` varchar(64) NULL,

  `assocDeviceName` varchar(32) NULL,
  `assocDeviceType` varchar(32) NULL,
  `chassisId` int(10) NULL,
  `assocType` varchar(32) NULL,

  `version` varchar(10) NULL,
  
  PRIMARY KEY `id` (`id`),
  UNIQUE KEY `deviceName` (`deviceName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  
-- -----------------------------------------------------------------------------

--
-- Table structure for table `blade`
--
DROP TABLE IF EXISTS `blade`;
CREATE TABLE IF NOT EXISTS `blade` (
  `id` int(10) NOT NULL,
  `deviceName` varchar(32) NOT NULL,
  `fullDnsName` varchar(64) NULL,

  `queried` tinyint(1) NOT NULL default '0',
  `inCMDB` tinyint(1) NOT NULL default '0',

  `hwStatus` varchar(32) NULL,
  `mpStatus` varchar(32) NULL,
  `swStatus` varchar(32) NULL,
  `vmmStatus` varchar(32) NULL,
  `pmpStatus` int(2) NULL,
  `deviceType` varchar(32) NULL,
  `deviceAddress` varchar(16) NULL,
  `productName` varchar(64) NULL,
  `osName` varchar(64) NULL,

  `assocDeviceName` varchar(32) NULL,
  `assocDeviceType` varchar(32) NULL,
  `chassisId` int(10) NULL,
  `assocType` varchar(32) NULL,

  `serialNumber` varchar(32) NULL,
  `memorySize` int(10) NULL,    
  `romVersion` varchar(32) NULL,
  `numberOfCpus` int(2) NULL,
  `slotNumber` int(2) NULL,
  
  `distNetworkCidr` varchar(20) NULL,
  `distSwitchName` varchar(40) NULL,

  `sysId` char(33) NOT NULL,
  `environment` varchar(64) default NULL,
  `cmHwStatus` varchar(32) default NULL,
  `businessService` varchar(64) default NULL,
  `subsystem` varchar(64) default NULL,
  `opsSuppMgr` varchar(64) default NULL,
  `opsSuppGrp` varchar(64) default NULL,
  
  `comments` blob default NULL,
  `shortDescr` blob default NULL,

  PRIMARY KEY `id` (`id`),
  UNIQUE KEY `deviceName` (`deviceName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  
-- -----------------------------------------------------------------------------

--
-- Table structure for table `blade_wwn`
--
DROP TABLE IF EXISTS `blade_wwn`;
CREATE TABLE IF NOT EXISTS `blade_wwn` (
  `id` int(10) NOT NULL auto_increment,
  `bladeId` int(10) NOT NULL,
  `wwn` varchar(24) NOT NULL,
  `port` int(2) NULL,
  `fabricName` varchar(20) NULL,
  `speed` varchar(16) NULL,
  `status` varchar(16),

  PRIMARY KEY `id` (`id`),
  KEY `bladeId` (`bladeId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  
-- -----------------------------------------------------------------------------

--
-- Table structure for table `vm`
--
DROP TABLE IF EXISTS `vm`;
CREATE TABLE IF NOT EXISTS `vm` (
  `id` int(10) NOT NULL auto_increment,
  `bladeId` int(10) NOT NULL,
  
  `deviceName` varchar(32) NOT NULL,
  `fullDnsName` varchar(64) NULL,

  `active` tinyint(1) NOT NULL default '0',
  `inCMDB` tinyint(1) NOT NULL default '0',

  `osName` varchar(64) NULL,
  `osVersion` varchar(32) NULL,
  `osPatchLevel` varchar(32) NULL,
    
  `memorySize` int(10) NULL,    
  `numberOfCpus` int(2) NULL,
  
  `sysId` char(33) NOT NULL,
  `environment` varchar(632) default NULL,
  `cmHwStatus` varchar(32) default NULL,
  `businessService` varchar(64) default NULL,
  `subsystem` varchar(64) default NULL,
  `opsSuppMgr` varchar(64) default NULL,
  `opsSuppGrp` varchar(64) default NULL,
  
  `comments` blob default NULL,
  `shortDescr` blob default NULL,
  
  PRIMARY KEY `id` (`id`),
  UNIQUE KEY `sysId` (`sysId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
    
-- -----------------------------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(5) NOT NULL auto_increment,
  `firstName` varchar(32) NOT NULL,
  `lastName` varchar(32) NULL,
  `userName` varchar(32) NOT NULL,

  `empId` varchar(16) NULL,
  `title` varchar(32) NULL,
  `dept` varchar(32) NULL,
  `office` varchar(32) NULL,
  `email` varchar(64) NULL,

  `officePhone` varchar(16) NULL,
  `mobilePhone` varchar(16) NULL,
  
  `accessCode` int(1) NOT NULL default 0,
 
  `dateCreated` timestamp NULL,
  `userCreated` varchar(16) NOT NULL default 'stsuser',
  `dateUpdated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `userUpdated` varchar(16) NOT NULL default 'stsuser',

  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci auto_increment=1;

-- -----------------------------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE IF NOT EXISTS `login` (
  `userId` int(5) NOT NULL,
  `numLogins` int(4) NOT NULL,
  `lastLogin` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ipAddr` varchar(24),
  `userAgent` varchar(132) default NULL,
  
  KEY `userId` (`userId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci auto_increment=1;

-- -----------------------------------------------------------------------------

--
-- Table structure for table `page_view`
--

CREATE TABLE IF NOT EXISTS `page_view` (
  `userId` int(5) NOT NULL,
  `page` varchar(200),
  `accessTime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  
  KEY `userId` (`userId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci auto_increment=1;

-- -----------------------------------------------------------------------------
-- -----------------------------------------------------------------------------

--
-- Table constraints
--

--
-- Constraints for table `chassis_wwn`                                            
--
ALTER TABLE `chassis_wwn`
  ADD CONSTRAINT `chassis_wwn_chassisId_fk` FOREIGN KEY (`chassisId`) REFERENCES `chassis` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
  
--
-- Constraints for table `mgmt_processor`                                            
--
ALTER TABLE `mgmt_processor`
  ADD CONSTRAINT `mgmt_procs_chassisId_fk` FOREIGN KEY (`chassisId`) REFERENCES `chassis` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
  
--
-- Constraints for table `switch`                                            
--
ALTER TABLE `switch`
  ADD CONSTRAINT `switch_chassisId_fk` FOREIGN KEY (`chassisId`) REFERENCES `chassis` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
  
--
-- Constraints for table `blade`                                            
--
ALTER TABLE `blade`
  ADD CONSTRAINT `blade_chassisId_fk` FOREIGN KEY (`chassisId`) REFERENCES `chassis` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
  
--
-- Constraints for table `blade_wwn`                                            
--
ALTER TABLE `blade_wwn`
  ADD CONSTRAINT `blade_wwn_bladeId_fk` FOREIGN KEY (`bladeId`) REFERENCES `blade` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
  
--
-- Constraints for table `vm`                                            
--
ALTER TABLE `vm`
  ADD CONSTRAINT `vm_bladeId_fk` FOREIGN KEY (`bladeId`) REFERENCES `blade` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
  
--
-- Constraints for table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `login_userId_fk` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON UPDATE NO ACTION;

--
-- Constraints for table `page_view`
--
ALTER TABLE `page_view`
  ADD CONSTRAINT `page_views_userId_fk` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON UPDATE NO ACTION;

