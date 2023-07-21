-- -----------------------------------------------------------------------------
--
-- $Id: bladerunner-r4.3-migrate.sql 73943 2013-04-04 15:28:35Z rcallaha $
-- $Date: 2013-04-04 11:28:35 -0400 (Thu, 04 Apr 2013) $
-- $Author: rcallaha $
-- $Revision: 73943 $
-- $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/sql/bladerunner-r4.3-migrate.sql $
--
-- -----------------------------------------------------------------------------

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `bladerunner`
--
USE `bladerunner`;

-- -----------------------------------------------------------------------------

--
-- Table structure changes for table `chassis`
--
ALTER TABLE `chassis`
  CHANGE `cmHwStatus` `cmInstallStatus` varchar(32) default NULL;

-- -----------------------------------------------------------------------------

--
-- Table structure changes for table `blade`
--

ALTER TABLE `blade`
  ADD `cmdbName`   varchar(40) NULL default NULL AFTER `fullDnsName`,
  ADD `iLo`        varchar(15) NULL default NULL AFTER `cmdbName`;

ALTER TABLE `blade`
  CHANGE `cmHwStatus` `cmInstallStatus` varchar(32) default NULL;

-- -----------------------------------------------------------------------------

--
-- Table structure changes for table `vm`
--
ALTER TABLE `vm`
    DROP INDEX sysId;

ALTER TABLE `vm`
  CHANGE `cmHwStatus` `cmInstallStatus` varchar(32) default NULL;

-- -----------------------------------------------------------------------------

-- --
-- Table structure changes for table `user`
--
ALTER TABLE `user`
  DROP `dateCreated`,
  DROP `userCreated`,
  DROP `dateUpdated`,
  DROP `userUpdated`;

-- -----------------------------------------------------------------------------

-- --
-- Table structure changes for table `exception_type`
--
DROP TABLE IF EXISTS `exception_type`;
CREATE TABLE IF NOT EXISTS `exception_type` (
  `id` int(10) NOT NULL auto_increment,
  `exceptionNumber` int(3) NOT NULL,
  `exceptionObject` varchar(16) collate utf8_unicode_ci NOT NULL,
  `exceptionDescr` varchar(200) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=11 ;

--
-- Dumping data for table `exception_type`
--

INSERT INTO `exception_type` (`id`, `exceptionNumber`, `exceptionObject`, `exceptionDescr`) VALUES
(1, 2, 'blade', 'HPSIM Name: S/N, Power: On/Off, CMDB: S/N'),
(2, 3, 'blade', 'HPSIM Name: S/N, Power: On/Off, CMDB: FQDN'),
(4, 4, 'blade', 'HPSIM Name: S/N, Power: On/Off, CMDB: Not Found'),
(5, 5, 'blade', 'HPSIM Name: Aquiring, Power: On/Off, CMDB: Not Found'),
(6, 6, 'blade', 'HPSIM Name: FQDN, Power: On/Off, CMDB: Not Found'),
(7, 8, 'vm', 'VM no longer exists and not marked as Decommissioned in CMDB'),
(8, 7, 'vm', 'VM not found in CMDB'),
(9, 1, 'blade', 'HPSIM Name: S/N, Power: On, CMDB: ''inventory'''),
(10, 9, 'mgmtproc', 'Could not query management processor'),
(11, 10, 'vm', 'VM exists; marked as Decommissioned in CMDB');

-- -----------------------------------------------------------------------------

--
-- Table structure changes for table `vm_exception`
--

DROP TABLE IF EXISTS `vm_exception`;
CREATE TABLE IF NOT EXISTS `vm_exception` (
  `id` int(10) NOT NULL auto_increment,
  `vmId` int(10) NOT NULL,
  `exceptionTypeId` int(5) NOT NULL,
  `dateUpdated` timestamp NULL default NULL,
  `userUpdated` varchar(32) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `vmId` (`vmId`),
  KEY `exceptionTypeId` (`exceptionTypeId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=471 ;

-- --------------------------------------------------------

--
-- Table structure for table `mgmt_processor_exception`
--

DROP TABLE IF EXISTS `mgmt_processor_exception`;
CREATE TABLE IF NOT EXISTS `mgmt_processor_exception` (
  `id` int(10) NOT NULL auto_increment,
  `mgmtProcessorId` int(10) NOT NULL,
  `exceptionTypeId` int(5) NOT NULL,
  `dateUpdated` timestamp NULL default NULL,
  `userUpdated` varchar(32) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `mgmtProcessorId` (`mgmtProcessorId`),
  KEY `exceptionTypeId` (`exceptionTypeId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=119 ;

-- -----------------------------------------------------------------------------

--
-- Constraints for dumped tables
--

--
-- Constraints for table `vm_exception`
--
ALTER TABLE `vm_exception`
  ADD CONSTRAINT `vm_exception_vmId_fk` FOREIGN KEY (`vmId`) REFERENCES `vm` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vm_exception_exceptionTypeId_fk` FOREIGN KEY (`exceptionTypeId`) REFERENCES `exception_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- -----------------------------------------------------------------------------

--
-- Constraints for table `mgmt_processor_exception`
--
ALTER TABLE `mgmt_processor_exception`
  ADD CONSTRAINT `mp_exception_bladeId_fk` FOREIGN KEY (`mgmtProcessorId`) REFERENCES `mgmt_processor` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `mp_exception_exceptionTypeId_fk` FOREIGN KEY (`exceptionTypeId`) REFERENCES `exception_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
