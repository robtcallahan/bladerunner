-- -----------------------------------------------------------------------------
--
-- $Id: bladerunner-r4.6-migrate.sql 74959 2013-05-06 16:47:01Z rcallaha $
-- $Date: 2013-05-06 12:47:01 -0400 (Mon, 06 May 2013) $
-- $Author: rcallaha $
-- $Revision: 74959 $
-- $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/sql/bladerunner-r4.6-migrate.sql $
--
-- -----------------------------------------------------------------------------

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Create the new database: `hpsim`
--
DROP DATABASE IF EXISTS `hpsim`;
CREATE DATABASE `hpsim` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

--
-- Move the bladerunner tables to hpsim
--
USE `bladerunner`;
RENAME TABLE `bladerunner`.`blade` TO `hpsim`.`blade` ;
RENAME TABLE `bladerunner`.`blade_exception` TO `hpsim`.`blade_exception` ;
RENAME TABLE `bladerunner`.`blade_reservation` TO `hpsim`.`blade_reservation` ;
RENAME TABLE `bladerunner`.`blade_wwn` TO `hpsim`.`blade_wwn` ;
RENAME TABLE `bladerunner`.`chassis` TO `hpsim`.`chassis` ;
RENAME TABLE `bladerunner`.`chassis_wwn` TO `hpsim`.`chassis_wwn` ;
RENAME TABLE `bladerunner`.`exception_type` TO `hpsim`.`exception_type` ;
RENAME TABLE `bladerunner`.`mgmt_processor` TO `hpsim`.`mgmt_processor` ;
RENAME TABLE `bladerunner`.`mgmt_processor_exception` TO `hpsim`.`mgmt_processor_exception` ;
RENAME TABLE `bladerunner`.`switch` TO `hpsim`.`switch` ;
RENAME TABLE `bladerunner`.`vlan` TO `hpsim`.`vlan` ;
RENAME TABLE `bladerunner`.`vm` TO `hpsim`.`vm` ;
RENAME TABLE `bladerunner`.`vm_exception` TO `hpsim`.`vm_exception` ;

-- -----------------------------------------------------------------------------
