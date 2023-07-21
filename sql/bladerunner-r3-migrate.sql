-- -----------------------------------------------------------------------------
--
-- $Id: bladerunner-r3-migrate.sql 68263 2012-09-17 19:35:11Z rcallaha $
-- $Date: 2012-09-17 15:35:11 -0400 (Mon, 17 Sep 2012) $
-- $Author: rcallaha $
-- $Revision: 68263 $
-- $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/sql/bladerunner-r3-migrate.sql $
--
-- -----------------------------------------------------------------------------

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `bladerunner`
--
USE `bladerunner`;

-- -----------------------------------------------------------------------------

--
-- Table structure for table `mgmt_processor`
--                                                                                                     
ALTER TABLE `mgmt_processor`
  ADD `role` varchar(20) default NULL AFTER `version`;

  
--
-- Table structure for table `blade`
--                                                                                                     
ALTER TABLE `blade`
  ADD `powerStatus` varchar(10) default NULL AFTER `pmpStatus`;
  
ALTER TABLE `blade`
  CHANGE `memorySize` `memorySizeGB` int(5) default NULL,
  CHANGE `numberOfCpus` `numCpus` int(2) default NULL,
  ADD `numCoresPerCpu` int(2) default NULL AFTER `numCpus`;

--
-- Table structure for table `vlan`
--                                                                                                     
ALTER TABLE `vlan`
  CHANGE `name` `name` varchar(64) NOT NULL;
  
-- -----------------------------------------------------------------------------
--
-- Constraints for table 
--
  

