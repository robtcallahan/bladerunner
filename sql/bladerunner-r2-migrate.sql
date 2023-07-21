-- -----------------------------------------------------------------------------
--
-- $Id: bladerunner-r2-migrate.sql 68258 2012-09-17 19:28:50Z rcallaha $
-- $Date: 2012-09-17 15:28:50 -0400 (Mon, 17 Sep 2012) $
-- $Author: rcallaha $
-- $Revision: 68258 $
-- $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/sql/bladerunner-r2-migrate.sql $
--
-- -----------------------------------------------------------------------------

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `bladerunner`
--
USE `bladerunner`;

-- -----------------------------------------------------------------------------

--
-- Table structure for table `chassis`
--                                                                                                     
ALTER TABLE `chassis`
  ADD `comments` blob default NULL AFTER `opsSuppGrp`,
  ADD `shortDescr` blob default NULL AFTER `comments`;

--
-- Table structure for table `vlan`
--
CREATE TABLE IF NOT EXISTS `vlan` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `switchId` int(10) NOT NULL,
  
  `name` varchar(32) NOT NULL,
  `status` varchar(16) NOT NULL,
  `sharedUplinkSet` varchar(32) NOT NULL,
  `vlanId` varchar(16) NOT NULL,
  `nativeVlan` varchar(16) NOT NULL,
  `private` varchar(16) NOT NULL,
  `preferredSpeed` varchar(16) NOT NULL,
  
  PRIMARY KEY `id` (`id`),
  KEY `switchId` (`switchId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  
-- -----------------------------------------------------------------------------
--
-- Constraints for table `vlan`                                            
--
ALTER TABLE `vlan`
  ADD CONSTRAINT `vlan_switchId_fk` FOREIGN KEY (`switchId`) REFERENCES `switch` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
  

