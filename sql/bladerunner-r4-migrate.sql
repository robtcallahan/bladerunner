-- -----------------------------------------------------------------------------
--
-- $Id: bladerunner-r4-migrate.sql 72101 2013-02-07 14:06:48Z rcallaha $
-- $Date: 2013-02-07 09:06:48 -0500 (Thu, 07 Feb 2013) $
-- $Author: rcallaha $
-- $Revision: 72101 $
-- $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/sql/bladerunner-r4-migrate.sql $
--
-- -----------------------------------------------------------------------------

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `bladerunner`
--
USE `bladerunner`;

-- -----------------------------------------------------------------------------

--
-- Table structure for table `blade`
--
ALTER TABLE `blade`
  ADD `isInventory`        int(1) default 0 AFTER `inCmdb`,
  ADD `isSpare`            int(1) default 0 AFTER `isInventory`;
  
--
-- Table structure for table `blade_reservation`
--
DROP TABLE IF EXISTS `blade_reservation`;
CREATE TABLE IF NOT EXISTS `blade_reservation` (
  `id`            int(10) NOT NULL AUTO_INCREMENT,
  `bladeId`       int(10) NOT NULL,
  
  `taskNumber`    varchar(16) NOT NULL,
  `taskSysId`     varchar(32) NOT NULL,
  `taskShortDescr` varchar(200) NULL default NULL,
  `projectName`   varchar(64) NOT NULL,
  
  `dateReserved`  timestamp NULL default CURRENT_TIMESTAMP,
  `userReserved`  varchar(32) NOT NULL,
  `dateUpdated`   timestamp NULL default NULL,
  `userUpdated`   varchar(32) NULL default NULL,
  `dateCompleted` timestamp NULL default NULL,
  `userCompleted` varchar(32) NULL default NULL,
  `dateCancelled` timestamp NULL default NULL,
  `userCancelled` varchar(32) NULL default NULL,
  PRIMARY KEY  (`id`),
  KEY `bladeId` (`bladeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0;

--
-- Table structure for table `blade_exception`
--
DROP TABLE IF EXISTS `blade_exception`;
CREATE TABLE IF NOT EXISTS `blade_exception` (
  `id`              int(10) NOT NULL AUTO_INCREMENT,
  `bladeId`         int(10) NOT NULL,
  `exceptionTypeId` int(5) NOT NULL,
  
  `dateUpdated`     timestamp NULL default NULL,
  `userUpdated`     varchar(32) NULL default NULL,
  PRIMARY KEY  (`id`),
  KEY `bladeId` (`bladeId`),
  KEY `exceptionTypeId` (`exceptionTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0;

--
-- Table structure for table `exception_type`
--
DROP TABLE IF EXISTS `exception_type`;
CREATE TABLE IF NOT EXISTS `exception_type` (
  `id`              int(10) NOT NULL AUTO_INCREMENT,
  `exceptionObject` varchar(16) NOT NULL,
  `exceptionDescr`  varchar(200) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0;

--
-- Table structure for table `user`
--                                                                                                     
ALTER TABLE `user`
  CHANGE `nickName` `nickName` VARCHAR(32) NULL DEFAULT NULL; 
  
-- -----------------------------------------------------------------------------
  
--                                                               
-- Constraints for table `blade_reservation`
--
ALTER TABLE `blade_reservation` 
  ADD CONSTRAINT `blade_reservation_bladeId_fk` FOREIGN KEY (`bladeId`) REFERENCES `blade` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;

--                                                               
-- Constraints for table `blade_exception`
--
ALTER TABLE `blade_exception` 
  ADD CONSTRAINT `blade_exception_bladeId_fk` FOREIGN KEY (`bladeId`) REFERENCES `blade` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `blade_exception_exceptionTypeId_fk` FOREIGN KEY (`exceptionTypeId`) REFERENCES `exception_type` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;

-- -----------------------------------------------------------------------------

--
-- Data for table `exception_type`
--
INSERT INTO `exception_type` (`id`, `exceptionObject`, `exceptionDescr`) VALUES
(1, 'blade', 'HPSIM Name: S/N, Power: Off, CMDB: S/N'),
(2, 'blade', 'HPSIM Name: S/N, Power: Off, CMDB: FQDN'),
(3, 'blade', 'HPSIM Name: S/N, Power: On, CMDB: FQDN'),
(4, 'blade', 'HPSIM Name: S/N, Power: Off/On, CMDB: Not Found'),
(5, 'blade', 'HPSIM Name: FQDN, Power: Off/On, CMDB: Not Found'),
(6, 'blade', 'HPSIM Name: Aquiring, Power: Off/On, CMDB: Not Found');

