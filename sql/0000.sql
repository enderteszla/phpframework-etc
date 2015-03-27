CREATE TABLE IF NOT EXISTS `Image` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Width` INT(11) NOT NULL,
  `Height` INT(11) NOT NULL,
  `URL` VARCHAR(80) NOT NULL
) ENGINE='InnoDB' DEFAULT CHARSET='utf8';

CREATE TABLE IF NOT EXISTS `Role` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(80) NOT NULL,
  UNIQUE KEY `Name` (`Name`)
) ENGINE='InnoDB' DEFAULT CHARSET='utf8';

CALL AddUniqueKeyIfNotExists('Role','Name');

CREATE TABLE IF NOT EXISTS `User` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Email` VARCHAR(255) NOT NULL,
  `Password` VARCHAR(80) NOT NULL,
  `FirstName` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' DEFAULT NULL,
  `LastName` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' DEFAULT NULL,
  `RoleID` INT DEFAULT NULL,
  `Active` TINYINT(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `Email` (`Email`),
  FOREIGN KEY(`RoleID`) REFERENCES `Role`(`ID`)
) ENGINE='InnoDB' DEFAULT CHARSET='utf8';

CALL AddUniqueKeyIfNotExists('User','Email');
CALL Add_ModifyColumn('User','RoleID','INT DEFAULT NULL AFTER `LastName`');
CALL AddForeignKeyIfNotExists('User','RoleID','Role','ID');

CREATE TABLE IF NOT EXISTS `Token` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Content` CHAR(32) NOT NULL,
  `Type` ENUM('session','activate','restorePassword') NOT NULL DEFAULT 'session',
  `UserID` INT(11) DEFAULT NULL,
  `Created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `Content` (`Content`),
  FOREIGN KEY (`UserID`) REFERENCES `User` (`ID`)
) ENGINE='InnoDB' DEFAULT CHARSET='utf8';

CALL AddUniqueKeyIfNotExists('Token','Content');
CALL AddForeignKeyIfNotExists('Token','UserID','User','ID');

INSERT IGNORE INTO `Token`(`Content`) VALUES('20c9af447201707825e83fb892a7cdc9');
INSERT IGNORE INTO `Role`(`Name`) VALUES('admin');